import 'dotenv/config';

import process from 'node:process';
import fs from 'node:fs/promises';
import path from 'node:path';
import { spawn } from 'node:child_process';

import { createPool, getMysqlConfigFromEnv } from './db.mjs';
import { createLogger } from './logger.mjs';
import { loadAgentConfigFromEnv, replayTemplate } from './replay.mjs';
import { analyzeScreenshot, warmupOcrWorker } from './vision.mjs';
import { uploadScreenshot } from './upload.mjs';

const logger = createLogger(process.env);

process.on('unhandledRejection', (reason) => {
  logger?.error?.('agent.unhandled_rejection', { error: String(reason) });
});
process.on('uncaughtException', (err) => {
  logger?.error?.('agent.uncaught_exception', { error: String(err?.message || err) });
});

async function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function toPositiveInt(value, fallback) {
  const parsed = Number(value);
  if (!Number.isFinite(parsed) || parsed <= 0) return fallback;
  return Math.floor(parsed);
}

function toNonNegativeInt(value, fallback) {
  const parsed = Number(value);
  if (!Number.isFinite(parsed) || parsed < 0) return fallback;
  return Math.floor(parsed);
}

function toBool(value, fallback = false) {
  if (value == null) return fallback;
  const v = String(value).trim().toLowerCase();
  if (v === '1' || v === 'true' || v === 'yes' || v === 'y') return true;
  if (v === '0' || v === 'false' || v === 'no' || v === 'n') return false;
  return fallback;
}

function psQuote(value) {
  return String(value ?? '').replace(/'/g, "''");
}

function normalizeForKeywordMatch(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ')
    .trim()
    .toUpperCase();
}

async function focusAppWindowByExePath(appExePath, waitMs = 350, localLogger = null) {
  if (process.platform !== 'win32') {
    return {
      focused: false,
      reason: 'non_windows',
      pid: null,
    };
  }

  const resolvedExePath = String(appExePath || '').trim();
  if (!resolvedExePath) {
    return {
      focused: false,
      reason: 'empty_path',
      pid: null,
    };
  }

  const exeName = path.parse(resolvedExePath).name;
  if (!exeName) {
    return {
      focused: false,
      reason: 'invalid_exe_name',
      pid: null,
    };
  }

  const safePath = psQuote(resolvedExePath);
  const safeExe = psQuote(exeName);
  const psScript = [
    `$targetPath='${safePath}';`,
    `$targetExe='${safeExe}';`,
    '$procs=Get-Process -Name $targetExe -ErrorAction SilentlyContinue | Where-Object { $_.MainWindowHandle -ne 0 };',
    "if (-not $procs) { Write-Output 'NO_PROCESS'; exit 0 }",
    '$resolvedPath=$null;',
    'try { if ($targetPath -and (Test-Path $targetPath)) { $resolvedPath=(Resolve-Path $targetPath).Path } } catch {}',
    '$picked=$null;',
    'if ($resolvedPath) {',
    '  foreach ($p in $procs) {',
    '    try { if ($p.Path -eq $resolvedPath) { $picked=$p; break } } catch {}',
    '  }',
    '}',
    'if (-not $picked) { $picked=$procs | Sort-Object StartTime -Descending | Select-Object -First 1 }',
    '$shell=New-Object -ComObject WScript.Shell;',
    '$ok=$shell.AppActivate($picked.Id);',
    'if ($ok) { Write-Output ("FOCUSED:" + $picked.Id); } else { Write-Output ("NOT_FOCUSED:" + $picked.Id); }',
  ].join(' ');

  const output = await new Promise((resolve, reject) => {
    const child = spawn('powershell.exe', [
      '-NoProfile',
      '-ExecutionPolicy',
      'Bypass',
      '-Command',
      psScript,
    ], {
      windowsHide: true,
    });

    let stdout = '';
    let stderr = '';
    child.stdout.on('data', (chunk) => {
      stdout += String(chunk || '');
    });
    child.stderr.on('data', (chunk) => {
      stderr += String(chunk || '');
    });
    child.on('error', reject);
    child.on('close', (code) => {
      if (code !== 0) {
        reject(new Error(stderr || `Falha no foco do app (code ${code}).`));
        return;
      }
      resolve(String(stdout || '').trim());
    });
  });

  const normalizedOutput = String(output || '').trim();
  if (normalizedOutput === 'NO_PROCESS') {
    return {
      focused: false,
      reason: 'process_not_found',
      pid: null,
    };
  }

  const focusedMatch = normalizedOutput.match(/^FOCUSED:(\d+)$/i);
  if (focusedMatch) {
    const pid = Number(focusedMatch[1]);
    const safeWaitMs = toNonNegativeInt(waitMs, 350);
    if (safeWaitMs > 0) {
      await sleep(safeWaitMs);
    }
    localLogger?.info?.('preflight.focus.ok', {
      exePath: resolvedExePath,
      pid: Number.isFinite(pid) ? pid : null,
      wait_ms: safeWaitMs,
    });
    return {
      focused: true,
      reason: 'ok',
      pid: Number.isFinite(pid) ? pid : null,
    };
  }

  const notFocusedMatch = normalizedOutput.match(/^NOT_FOCUSED:(\d+)$/i);
  if (notFocusedMatch) {
    const pid = Number(notFocusedMatch[1]);
    return {
      focused: false,
      reason: 'app_activate_failed',
      pid: Number.isFinite(pid) ? pid : null,
    };
  }

  return {
    focused: false,
    reason: 'unknown_output',
    pid: null,
  };
}

function resolvePreflightMinMatches(agentCfg, expectedKeywords) {
  const fallback = expectedKeywords.length > 0 ? 1 : 0;
  const configured = toNonNegativeInt(agentCfg?.preflightMinKeywordMatches, fallback);
  return Math.min(configured, expectedKeywords.length);
}

async function runPreflightFocusAndOcr({
  requestId,
  agentCfg,
  logger: localLogger,
}) {
  if (!agentCfg?.preflightEnabled) {
    return {
      skipped: true,
      reason: 'disabled',
      screenshotPath: null,
      matchedKeywords: [],
      requiredMatches: 0,
    };
  }

  const focusExePath = String(agentCfg?.preflightFocusExePath || '').trim();
  const focusWaitMs = toNonNegativeInt(agentCfg?.preflightFocusWaitMs, 350);
  const requireFocus = Boolean(agentCfg?.preflightRequireFocus);
  const runOcr = Boolean(agentCfg?.preflightOcrEnabled);
  const expectedKeywordsRaw = Array.isArray(agentCfg?.preflightExpectedKeywords)
    ? agentCfg.preflightExpectedKeywords
    : [];
  const expectedKeywords = expectedKeywordsRaw
    .map((item) => String(item || '').trim())
    .filter(Boolean);
  const requiredMatches = resolvePreflightMinMatches(agentCfg, expectedKeywords);

  localLogger?.info?.('preflight.begin', {
    request_id: requestId,
    focus_exe_path: focusExePath || null,
    require_focus: requireFocus,
    ocr_enabled: runOcr,
    expected_keywords: expectedKeywords,
    min_keyword_matches: requiredMatches,
  });

  let focusResult = null;
  if (focusExePath) {
    focusResult = await focusAppWindowByExePath(focusExePath, focusWaitMs, localLogger);
  } else {
    focusResult = {
      focused: false,
      reason: 'focus_path_missing',
      pid: null,
    };
  }

  if (requireFocus && !focusResult?.focused) {
    throw new Error(
      `Preflight de foco falhou: ${focusResult?.reason || 'focus_failed'} (exe: ${focusExePath || 'n/a'})`
    );
  }

  if (!runOcr) {
    localLogger?.info?.('preflight.done', {
      request_id: requestId,
      screenshot_path: null,
      ocr_enabled: false,
      mode: 'focus_only',
      focus: focusResult,
    });
    return {
      skipped: false,
      reason: 'focus_only',
      screenshotPath: null,
      matchedKeywords: [],
      requiredMatches: 0,
    };
  }

  const screenshotPath = await captureDesktopScreenshot({
    screenshotsDir: agentCfg?.screenshotsDir,
    requestId,
    retryIndex: 97,
    logger: localLogger,
  });

  const analysis = await analyzeScreenshot(screenshotPath, {
    provider: agentCfg?.preflightOcrProvider || 'local',
    lang: agentCfg?.ocrLang || 'por',
    maxPlates: agentCfg?.ocrMaxPlates || 18,
    openAiApiKey: agentCfg?.ocrOpenAiApiKey || '',
    openAiModel: agentCfg?.ocrOpenAiModel || '',
    openAiBaseUrl: agentCfg?.ocrOpenAiBaseUrl || '',
    openAiTimeoutMs: agentCfg?.ocrOpenAiTimeoutMs || 30000,
    openAiFallbackLocal: Boolean(agentCfg?.ocrOpenAiFallbackLocal),
    logger: localLogger,
  });
  const normalizedText = normalizeForKeywordMatch(analysis?.rawText || analysis?.normalizedText || '');
  const matchedKeywords = expectedKeywords.filter((keyword) =>
    normalizedText.includes(normalizeForKeywordMatch(keyword))
  );
  const isMatched = matchedKeywords.length >= requiredMatches;

  localLogger?.info?.('preflight.ocr.result', {
    request_id: requestId,
    screenshot_path: screenshotPath,
    matched_keywords: matchedKeywords,
    matched_count: matchedKeywords.length,
    required_matches: requiredMatches,
    expected_keywords: expectedKeywords,
    focus: focusResult,
  });

  if (agentCfg?.preflightFailIfNotMatched && !isMatched) {
    throw new Error(
      `Preflight OCR falhou: esperado >=${requiredMatches} palavra(s)-chave do e-System, encontrado ${matchedKeywords.length}.`
    );
  }

  return {
    skipped: false,
    reason: 'ok',
    screenshotPath,
    matchedKeywords,
    requiredMatches,
  };
}

function resolveTokenUpdaterConfig(env) {
  const enabled = toBool(env.AGENT_TOKEN_UPDATER_ENABLED, true);
  const cwdRaw = String(env.AGENT_TOKEN_UPDATER_DIR || '').trim();
  const cwd = cwdRaw
    ? path.resolve(process.cwd(), cwdRaw)
    : path.resolve(process.cwd(), '..', 'atualizacaoToken');

  return {
    enabled,
    cwd,
    command: String(env.AGENT_TOKEN_UPDATER_COMMAND || 'npm start').trim() || 'npm start',
    idleGraceMs: toNonNegativeInt(env.AGENT_TOKEN_UPDATER_IDLE_GRACE_MS, 2500),
    stopTimeoutMs: toPositiveInt(env.AGENT_TOKEN_UPDATER_STOP_TIMEOUT_MS, 15000),
  };
}

function createTokenUpdaterManager(env, localLogger) {
  const cfg = resolveTokenUpdaterConfig(env);
  let child = null;
  let idleSince = null;
  let stoppingPromise = null;
  let starting = false;
  let disabledByConfigError = false;

  const isRunning = () => Boolean(child && child.exitCode == null && !child.killed);

  const waitForExit = (proc, timeoutMs) => new Promise((resolve) => {
    if (!proc || proc.exitCode != null) {
      resolve(true);
      return;
    }

    let settled = false;
    let timer = null;
    const finish = (value) => {
      if (settled) return;
      settled = true;
      if (timer) clearTimeout(timer);
      proc.removeListener('exit', onExit);
      resolve(value);
    };

    const onExit = () => finish(true);
    timer = setTimeout(() => finish(false), Math.max(1000, timeoutMs || 10000));

    proc.once('exit', onExit);
  });

  const killProcessTree = async (pid) => {
    if (!Number.isInteger(pid) || pid <= 0) return;

    if (process.platform === 'win32') {
      await new Promise((resolve) => {
        const killer = spawn('taskkill.exe', ['/PID', String(pid), '/T', '/F'], { windowsHide: true });
        killer.on('error', () => resolve());
        killer.on('close', () => resolve());
      });
      return;
    }

    try {
      process.kill(pid, 'SIGTERM');
    } catch {
      // ignore
    }
  };

  const stopProcess = async (reason = 'busy') => {
    idleSince = null;

    if (!cfg.enabled || disabledByConfigError) return;
    if (!isRunning()) {
      child = null;
      return;
    }
    if (stoppingPromise) {
      await stoppingPromise;
      return;
    }

    const proc = child;
    const pid = proc?.pid ?? null;
    localLogger?.info?.('token_updater.stop.begin', { reason, pid });

    stoppingPromise = (async () => {
      try {
        await killProcessTree(pid);
        const exited = await waitForExit(proc, cfg.stopTimeoutMs);
        if (!exited && process.platform !== 'win32') {
          try {
            process.kill(pid, 'SIGKILL');
          } catch {
            // ignore
          }
          await waitForExit(proc, 3000);
        }
      } finally {
        if (child === proc) child = null;
      }
    })();

    try {
      await stoppingPromise;
      localLogger?.info?.('token_updater.stop.done', { reason, pid });
    } finally {
      stoppingPromise = null;
    }
  };

  const onQueueBusy = async (reason = 'queue_busy') => {
    await stopProcess(reason);
  };

  const onQueueEmpty = async (reason = 'queue_empty') => {
    if (!cfg.enabled || disabledByConfigError) return;

    if (!idleSince) {
      idleSince = Date.now();
      localLogger?.debug?.('token_updater.idle.begin', { reason, idleGraceMs: cfg.idleGraceMs });
    }

    if (isRunning()) return;
    if (starting || stoppingPromise) return;
    if (Date.now() - idleSince < cfg.idleGraceMs) return;

    starting = true;
    try {
      const packageJsonPath = path.join(cfg.cwd, 'package.json');
      try {
        await fs.access(packageJsonPath);
      } catch {
        disabledByConfigError = true;
        localLogger?.warn?.('token_updater.disabled_missing_package', {
          cwd: cfg.cwd,
          packageJsonPath,
        });
        return;
      }

      if (!idleSince) {
        return;
      }

      const proc = spawn(cfg.command, {
        cwd: cfg.cwd,
        env: process.env,
        shell: true,
        windowsHide: true,
        stdio: 'inherit',
      });

      child = proc;
      localLogger?.info?.('token_updater.start', {
        reason,
        pid: proc.pid ?? null,
        cwd: cfg.cwd,
        command: cfg.command,
      });

      proc.once('exit', (code, signal) => {
        if (child === proc) child = null;
        localLogger?.warn?.('token_updater.exit', {
          code: code ?? null,
          signal: signal ?? null,
        });
      });

      proc.once('error', (err) => {
        if (child === proc) child = null;
        localLogger?.error?.('token_updater.error', {
          error: String(err?.message || err),
        });
      });
    } finally {
      starting = false;
    }
  };

  const shutdown = async (reason = 'shutdown') => {
    await stopProcess(reason);
  };

  localLogger?.info?.('token_updater.config', {
    enabled: cfg.enabled,
    cwd: cfg.cwd,
    command: cfg.command,
    idleGraceMs: cfg.idleGraceMs,
    stopTimeoutMs: cfg.stopTimeoutMs,
  });

  return {
    enabled: cfg.enabled,
    onQueueBusy,
    onQueueEmpty,
    shutdown,
  };
}

async function captureDesktopScreenshot({
  screenshotsDir,
  requestId,
  retryIndex,
  logger: localLogger,
}) {
  if (process.platform !== 'win32') {
    localLogger?.warn?.('ocr.transient.capture_skipped_non_windows', { request_id: requestId });
    return null;
  }

  const outDir = path.resolve(process.cwd(), screenshotsDir || 'screenshots');
  await fs.mkdir(outDir, { recursive: true });
  const filePath = path.join(
    outDir,
    `shot_retry_${requestId}_${String(retryIndex).padStart(2, '0')}_${Date.now()}.png`
  );
  const quotedPath = psQuote(filePath);

  const psScript = [
    'Add-Type -AssemblyName System.Windows.Forms;',
    'Add-Type -AssemblyName System.Drawing;',
    '$bounds=[System.Windows.Forms.Screen]::PrimaryScreen.Bounds;',
    '$bmp=New-Object System.Drawing.Bitmap $bounds.Width, $bounds.Height;',
    '$g=[System.Drawing.Graphics]::FromImage($bmp);',
    '$g.CopyFromScreen($bounds.X, $bounds.Y, 0, 0, $bounds.Size);',
    '$g.Dispose();',
    `$bmp.Save('${quotedPath}', [System.Drawing.Imaging.ImageFormat]::Png);`,
    '$bmp.Dispose();',
  ].join(' ');

  await new Promise((resolve, reject) => {
    const child = spawn('powershell.exe', [
      '-NoProfile',
      '-ExecutionPolicy',
      'Bypass',
      '-Command',
      psScript,
    ], {
      windowsHide: true,
    });

    let stderr = '';
    child.stderr.on('data', (chunk) => {
      stderr += String(chunk || '');
    });
    child.on('error', reject);
    child.on('close', (code) => {
      if (code !== 0) {
        reject(new Error(stderr || `Falha ao capturar screenshot (code ${code}).`));
        return;
      }
      resolve();
    });
  });

  return filePath;
}

function isDbConnectionOrAuthError(err) {
  const code = String(err?.code || '');
  if (!code) return false;

  if (code.startsWith('ER_')) return true;

  const transientCodes = new Set([
    'ECONNREFUSED',
    'ECONNRESET',
    'ETIMEDOUT',
    'EHOSTUNREACH',
    'ENOTFOUND',
    'PROTOCOL_CONNECTION_LOST',
  ]);
  return transientCodes.has(code);
}

function isDbAuthError(err) {
  const code = String(err?.code || '');
  return code === 'ER_ACCESS_DENIED_ERROR' || code === 'ER_DBACCESS_DENIED_ERROR';
}

async function templateContainsSlot(templatePath, slotName) {
  if (!templatePath || !slotName) return false;
  const absTemplatePath = path.resolve(process.cwd(), templatePath);
  const raw = await fs.readFile(absTemplatePath, 'utf8');
  const normalized = raw.charCodeAt(0) === 0xfeff ? raw.slice(1) : raw;
  const parsed = JSON.parse(normalized);
  if (!Array.isArray(parsed)) return false;
  return parsed.some((event) => event?.type === 'slot_begin' && event?.name === slotName);
}

async function assertLoginTemplateHasSenha(templatePath) {
  const hasSenha = await templateContainsSlot(templatePath, 'senha');
  if (!hasSenha) {
    throw new Error(
      `Template de login inválido (${templatePath}): faltou slot_begin "senha". ` +
      'Isso pode causar cliques globais fora da janela do sistema.'
    );
  }
}

async function claimNextPending(connection) {
  // Trava o runner_state para garantir execução única
  const [stateRows] = await connection.query(
    'SELECT * FROM placas_zero_km_runner_state WHERE id = 1 FOR UPDATE'
  );
  if (!stateRows?.length) {
    throw new Error('Runner state não encontrado (placas_zero_km_runner_state id=1). Rode as migrations.');
  }

  const state = stateRows[0];
  if (Number(state.is_running) === 1) {
    const hb = state.last_heartbeat_at ? new Date(state.last_heartbeat_at) : null;
    const hbAgeMs = hb ? Date.now() - hb.getTime() : null;
    let stale = hb ? hbAgeMs > 10 * 60 * 1000 : true;
    let reason = stale ? 'heartbeat_timeout' : null;

    let currentStatus = null;
    if (state.current_request_id) {
      const [[row]] = await connection.query(
        'SELECT status FROM placas_zero_km_requests WHERE id = :id LIMIT 1',
        { id: state.current_request_id }
      );
      currentStatus = row?.status ?? null;
      if (currentStatus && currentStatus !== 'running') {
        stale = true;
        reason = 'request_not_running';
      }
    } else {
      stale = true;
      reason = 'no_current_request';
    }

    if (!stale) {
      logger?.debug?.('runner.busy', {
        current_request_id: state.current_request_id,
        hbAgeSec: hbAgeMs != null ? Math.round(hbAgeMs / 1000) : null,
        status: currentStatus,
      });
      return null;
    }

    await connection.query(
      'UPDATE placas_zero_km_runner_state SET is_running = 0, current_request_id = NULL, last_heartbeat_at = NOW() WHERE id = 1'
    );
    logger?.warn?.('runner.stale_reset', {
      prev_request_id: state.current_request_id,
      reason,
      hbAgeSec: hbAgeMs != null ? Math.round(hbAgeMs / 1000) : null,
      status: currentStatus,
    });
  }

  const [pendingRows] = await connection.query(
    "SELECT * FROM placas_zero_km_requests WHERE status = 'pending' ORDER BY id ASC LIMIT 1 FOR UPDATE"
  );
  if (!pendingRows?.length) {
    await connection.query('UPDATE placas_zero_km_runner_state SET last_heartbeat_at = NOW() WHERE id = 1');
    logger?.debug?.('queue.empty');
    return null;
  }

  const req = pendingRows[0];
  await connection.query(
    "UPDATE placas_zero_km_requests SET status = 'running', attempts = attempts + 1, started_at = NOW(), updated_at = NOW() WHERE id = :id",
    { id: req.id }
  );
  await connection.query(
    'UPDATE placas_zero_km_runner_state SET is_running = 1, current_request_id = :id, last_heartbeat_at = NOW(), updated_at = NOW() WHERE id = 1',
    { id: req.id }
  );
  await connection.query(
    "UPDATE placas_zero_km_batches SET status = 'running', updated_at = NOW() WHERE id = :batchId",
    { batchId: req.batch_id }
  );

  logger?.info?.('queue.claimed', { request_id: req.id, batch_id: req.batch_id });
  return req;
}

async function releaseRunner(connection, requestId) {
  await connection.query(
    'UPDATE placas_zero_km_runner_state SET is_running = 0, current_request_id = NULL, last_heartbeat_at = NOW(), updated_at = NOW() WHERE id = 1 AND current_request_id = :id',
    { id: requestId }
  );
  logger?.info?.('runner.released', { request_id: requestId });
}

async function refreshBatchCounters(connection, batchId) {
  const [[totalRow]] = await connection.query(
    'SELECT COUNT(*) as c FROM placas_zero_km_requests WHERE batch_id = :batchId',
    { batchId }
  );
  const [[okRow]] = await connection.query(
    "SELECT COUNT(*) as c FROM placas_zero_km_requests WHERE batch_id = :batchId AND status = 'succeeded'",
    { batchId }
  );
  const [[failRow]] = await connection.query(
    "SELECT COUNT(*) as c FROM placas_zero_km_requests WHERE batch_id = :batchId AND status = 'failed'",
    { batchId }
  );
  const [[runRow]] = await connection.query(
    "SELECT COUNT(*) as c FROM placas_zero_km_requests WHERE batch_id = :batchId AND status = 'running'",
    { batchId }
  );
  const [[pendRow]] = await connection.query(
    "SELECT COUNT(*) as c FROM placas_zero_km_requests WHERE batch_id = :batchId AND status = 'pending'",
    { batchId }
  );

  const total = Number(totalRow?.c || 0);
  const succeeded = Number(okRow?.c || 0);
  const failed = Number(failRow?.c || 0);
  const running = Number(runRow?.c || 0);
  const pending = Number(pendRow?.c || 0);
  const processed = succeeded + failed;

  const status = pending === 0 && running === 0 ? 'completed' : running > 0 ? 'running' : 'pending';

  await connection.query(
    'UPDATE placas_zero_km_batches SET status = :status, total = :total, processed = :processed, succeeded = :succeeded, failed = :failed, updated_at = NOW() WHERE id = :batchId',
    { status, total, processed, succeeded, failed, batchId }
  );
}

async function killAppProcessByExePath(appExePath, localLogger) {
  if (process.platform !== 'win32') return;
  if (!appExePath) return;

  const exeName = path.parse(String(appExePath)).name;
  if (!exeName) return;
  const safeName = psQuote(exeName);
  const psScript = `Get-Process -Name '${safeName}' -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue`;

  await new Promise((resolve) => {
    const child = spawn('powershell.exe', [
      '-NoProfile',
      '-ExecutionPolicy',
      'Bypass',
      '-Command',
      psScript,
    ], {
      windowsHide: true,
    });
    child.on('error', () => resolve());
    child.on('close', () => resolve());
  });

  localLogger?.info?.('app.killed', { exe: exeName });
}

function normalizeClickActions(actions) {
  if (!Array.isArray(actions)) return [];
  return actions
    .map((action) => ({
      x: Number(action?.x),
      y: Number(action?.y),
      clicks: Number(action?.clicks ?? 1),
    }))
    .filter((action) => Number.isFinite(action.x) && Number.isFinite(action.y))
    .map((action) => ({
      x: Math.round(action.x),
      y: Math.round(action.y),
      clicks: Math.max(1, Math.round(Number.isFinite(action.clicks) ? action.clicks : 1)),
    }));
}

async function runGlobalClickActions({
  actions,
  delayMs,
  requestId,
  logger: localLogger,
  doneEventName,
  errorLabel,
}) {
  if (process.platform !== 'win32') return;
  const normalizedActions = normalizeClickActions(actions);
  if (normalizedActions.length === 0) return;

  const clickDelayMs = toNonNegativeInt(delayMs, 140);
  const scriptParts = [
    `Add-Type -TypeDefinition 'using System; using System.Runtime.InteropServices; public static class WinInput { [DllImport("user32.dll")] public static extern bool SetCursorPos(int X, int Y); [DllImport("user32.dll")] public static extern void mouse_event(uint dwFlags, uint dx, uint dy, uint dwData, UIntPtr dwExtraInfo); public const uint MOUSEEVENTF_LEFTDOWN = 0x0002; public const uint MOUSEEVENTF_LEFTUP = 0x0004; }';`,
  ];

  for (const action of normalizedActions) {
    for (let i = 0; i < action.clicks; i += 1) {
      scriptParts.push(`[WinInput]::SetCursorPos(${action.x}, ${action.y}) | Out-Null;`);
      scriptParts.push('Start-Sleep -Milliseconds 45;');
      scriptParts.push('[WinInput]::mouse_event([WinInput]::MOUSEEVENTF_LEFTDOWN,0,0,0,[UIntPtr]::Zero);');
      scriptParts.push('Start-Sleep -Milliseconds 22;');
      scriptParts.push('[WinInput]::mouse_event([WinInput]::MOUSEEVENTF_LEFTUP,0,0,0,[UIntPtr]::Zero);');
      scriptParts.push(`Start-Sleep -Milliseconds ${clickDelayMs};`);
    }
  }

  const psScript = scriptParts.join(' ');

  await new Promise((resolve, reject) => {
    const child = spawn('powershell.exe', [
      '-NoProfile',
      '-ExecutionPolicy',
      'Bypass',
      '-Command',
      psScript,
    ], {
      windowsHide: true,
    });

    let stderr = '';
    child.stderr.on('data', (chunk) => {
      stderr += String(chunk || '');
    });
    child.on('error', reject);
    child.on('close', (code) => {
      if (code !== 0) {
        reject(new Error(stderr || `Falha ao executar ${errorLabel || 'ações de clique'} (code ${code}).`));
        return;
      }
      resolve();
    });
  });

  localLogger?.info?.(doneEventName || 'click.actions.done', {
    request_id: requestId,
    actions: normalizedActions,
    delay_ms: clickDelayMs,
  });
}

async function runPostResultModalCleanup({
  points,
  delayMs,
  requestId,
  logger: localLogger,
}) {
  await runGlobalClickActions({
    actions: points,
    delayMs,
    requestId,
    logger: localLogger,
    doneEventName: 'modal.cleanup.done',
    errorLabel: 'limpeza de modal',
  });
}

async function runTransientPersistenceRecovery({
  actions,
  delayMs,
  waitMs,
  requestId,
  logger: localLogger,
}) {
  await runGlobalClickActions({
    actions,
    delayMs,
    requestId,
    logger: localLogger,
    doneEventName: 'transient.persistence.done',
    errorLabel: 'persistência de modal',
  });

  const safeWaitMs = toNonNegativeInt(waitMs, 1000);
  if (safeWaitMs > 0) {
    await sleep(safeWaitMs);
  }
}

async function runStartupLogin(agentCfg) {
  if (!agentCfg.loginTemplatePath) return;
  if (!agentCfg.loginBootstrapOnStart) return;

  await assertLoginTemplateHasSenha(agentCfg.loginTemplatePath);

  logger?.info?.('agent.bootstrap_login.begin', {
    templatePath: agentCfg.loginTemplatePath,
    appExePath: agentCfg.appExePath || null,
  });

  const bootstrapData = {
    // replayTemplate exige cpf_cgc válido, mesmo sem slot no template de login.
    cpf_cgc: '00000000000',
    chassi: '',
    senha: agentCfg.loginPassword ?? '',
  };

  await replayTemplate({
    templatePath: agentCfg.loginTemplatePath,
    data: bootstrapData,
    screenshotsDir: agentCfg.screenshotsDir,
    maxDelayMs: agentCfg.maxDelayMs,
    speed: agentCfg.speed,
    replayText: agentCfg.replayText,
    replayVisualDebug: agentCfg.replayVisualDebug,
    replayVisualMs: agentCfg.replayVisualMs,
    replayVisualDotW: agentCfg.replayVisualDotW,
    replayVisualDotH: agentCfg.replayVisualDotH,
    replayVisualShowCard: agentCfg.replayVisualShowCard,
    preReplayWaitMs: 0,
    postLoginWaitMs: agentCfg.postLoginWaitMs,
    cropWidth: 0,
    cropHeight: 0,
    stopAtScreenshot: false,
    requireRequiredSlots: false,
    requireScreenshot: false,
    warnPasswordSlotMissing: false,
    passwordInputMode: agentCfg.passwordInputMode,
    passwordTypeDelayMs: agentCfg.passwordTypeDelayMs,
    passwordBeforeEnterMs: agentCfg.passwordBeforeEnterMs,
    appExePath: agentCfg.appExePath,
    appStartWaitMs: agentCfg.appStartWaitMs,
    autoEnterAfterClick: agentCfg.autoEnterAfterClick,
    autoEnterClickX: agentCfg.autoEnterClickX,
    autoEnterClickY: agentCfg.autoEnterClickY,
    autoEnterClickTolerance: agentCfg.autoEnterClickTolerance,
    autoEnterWaitBeforeMs: agentCfg.autoEnterWaitBeforeMs,
    autoEnterWaitAfterMs: agentCfg.autoEnterWaitAfterMs,
    appKillAfterScreenshot: false,
    exitAfterSenha: true,
    logger,
  });

  if (agentCfg.betweenTemplatesWaitMs > 0) {
    await sleep(agentCfg.betweenTemplatesWaitMs);
  }

  logger?.info?.('agent.bootstrap_login.done', {
    templatePath: agentCfg.loginTemplatePath,
  });
}

async function processOne(pool, agentCfg, tokenUpdaterManager = null) {
  const connection = await pool.getConnection();
  const finalizeFailure = async (reqId, batchId, errorMessage) => {
    let conn;
    try {
      conn = await pool.getConnection();
      await conn.beginTransaction();
      await conn.query(
        "UPDATE placas_zero_km_requests SET status='failed', response_error=:err, response_payload=NULL, finished_at=NOW(), updated_at=NOW() WHERE id=:id",
        { id: reqId, err: errorMessage }
      );
      await releaseRunner(conn, reqId);
      await refreshBatchCounters(conn, batchId);
      await conn.commit();
      logger?.info?.('request.failed.cleanup', { request_id: reqId });
    } catch (err) {
      logger?.error?.('request.failed.cleanup_error', { request_id: reqId, error: String(err?.message || err) });
    } finally {
      if (conn) conn.release();
    }
  };
  try {
    await connection.beginTransaction();
    const req = await claimNextPending(connection);
    await connection.commit();

    if (!req) return false;

    if (tokenUpdaterManager?.enabled) {
      try {
        await tokenUpdaterManager.onQueueBusy('request_claimed');
      } catch (stopErr) {
        logger?.warn?.('token_updater.stop_before_request_error', {
          request_id: req.id,
          error: String(stopErr?.message || stopErr),
        });
      }
    }

    let heartbeatTimer = null;
    const startHeartbeat = () => {
      if (heartbeatTimer) return;
      heartbeatTimer = setInterval(async () => {
        try {
          await connection.query(
            'UPDATE placas_zero_km_runner_state SET last_heartbeat_at = NOW(), updated_at = NOW() WHERE id = 1 AND current_request_id = :id',
            { id: req.id }
          );
        } catch {
          // ignore
        }
      }, 30_000);
    };
    const stopHeartbeat = () => {
      if (heartbeatTimer) clearInterval(heartbeatTimer);
      heartbeatTimer = null;
    };

    const data = {
      cpf_cgc: req.cpf_cgc ?? '',
      chassi: req.chassi,
      senha: agentCfg.loginPassword ?? '',
    };

    let result;
    let analysis = null;
    let uploadResult = null;
    let deferAppKillEnabled = false;
    let deferredAppExePath = '';
    let latestScreenshotPath = null;
    try {
      await runPreflightFocusAndOcr({
        requestId: req.id,
        agentCfg,
        logger,
      });

      logger?.info?.('replay.begin', { request_id: req.id });
      startHeartbeat();
      const shouldRunSeparateLogin = Boolean(agentCfg.loginTemplatePath) && !agentCfg.loginBootstrapOnStart;
      const templatePaths = Array.isArray(agentCfg.templatePaths) && agentCfg.templatePaths.length
        ? agentCfg.templatePaths
        : [agentCfg.templatePath];
      const multipleMainTemplates = templatePaths.length > 1;
      const appKillAfterRequest = agentCfg.loginBootstrapOnStart ? false : Boolean(agentCfg.appKillAfterScreenshot);

      if (shouldRunSeparateLogin) {
        await assertLoginTemplateHasSenha(agentCfg.loginTemplatePath);
        logger?.info?.('replay.login.begin', {
          request_id: req.id,
          templatePath: agentCfg.loginTemplatePath,
        });
        await replayTemplate({
          templatePath: agentCfg.loginTemplatePath,
          data,
          screenshotsDir: agentCfg.screenshotsDir,
          maxDelayMs: agentCfg.maxDelayMs,
          speed: agentCfg.speed,
          replayText: agentCfg.replayText,
          replayVisualDebug: agentCfg.replayVisualDebug,
          replayVisualMs: agentCfg.replayVisualMs,
          replayVisualDotW: agentCfg.replayVisualDotW,
          replayVisualDotH: agentCfg.replayVisualDotH,
          replayVisualShowCard: agentCfg.replayVisualShowCard,
          preReplayWaitMs: 0,
          postLoginWaitMs: agentCfg.postLoginWaitMs,
          cropWidth: 0,
          cropHeight: 0,
          stopAtScreenshot: false,
          requireRequiredSlots: false,
          requireScreenshot: false,
          warnPasswordSlotMissing: false,
          passwordInputMode: agentCfg.passwordInputMode,
          passwordTypeDelayMs: agentCfg.passwordTypeDelayMs,
          passwordBeforeEnterMs: agentCfg.passwordBeforeEnterMs,
          appExePath: agentCfg.appExePath,
          appStartWaitMs: agentCfg.appStartWaitMs,
          autoEnterAfterClick: agentCfg.autoEnterAfterClick,
          autoEnterClickX: agentCfg.autoEnterClickX,
          autoEnterClickY: agentCfg.autoEnterClickY,
          autoEnterClickTolerance: agentCfg.autoEnterClickTolerance,
          autoEnterWaitBeforeMs: agentCfg.autoEnterWaitBeforeMs,
          autoEnterWaitAfterMs: agentCfg.autoEnterWaitAfterMs,
          appKillAfterScreenshot: false,
          exitAfterSenha: true,
          logger,
        });
        logger?.info?.('replay.login.done', { request_id: req.id });
        if (agentCfg.betweenTemplatesWaitMs > 0) {
          logger?.info?.('replay.between_wait', {
            request_id: req.id,
            waitMs: agentCfg.betweenTemplatesWaitMs,
          });
          await sleep(agentCfg.betweenTemplatesWaitMs);
        }
      }

      const transientRetryWaitMs = toPositiveInt(agentCfg.transientRetryWaitMs, 8000);
      const transientRetryMaxRetries = toNonNegativeInt(agentCfg.transientRetryMaxRetries, 6);
      const transientRetryEnabled = Boolean(agentCfg.transientRetryEnabled) && transientRetryMaxRetries > 0;
      const transientPersistenceEnabled = Boolean(agentCfg.transientPersistenceEnabled);
      const transientPersistenceActions = normalizeClickActions(agentCfg.transientPersistenceActions);
      const transientPersistenceWaitMs = toNonNegativeInt(agentCfg.transientPersistenceWaitMs, 1000);
      const transientPersistenceClickDelayMs = toNonNegativeInt(
        agentCfg.transientPersistenceClickDelayMs,
        120
      );
      const deferAppKillUntilAfterRetries = transientRetryEnabled && appKillAfterRequest;
      deferAppKillEnabled = deferAppKillUntilAfterRetries;
      deferredAppExePath = agentCfg.appExePath || '';
      const transientRetryChecks = [];
      let transientRetryTriggered = false;
      let transientPersistenceRuns = 0;
      const runTransientPersistenceIfNeeded = async ({
        currentAnalysis,
        stage = 'unknown',
        retryIndex = 0,
      } = {}) => {
        if (!transientPersistenceEnabled) return false;
        if (!currentAnalysis?.transientMessage) return false;
        if (!transientPersistenceActions.length) return false;

        transientPersistenceRuns += 1;
        logger?.info?.('ocr.transient.persistence.begin', {
          request_id: req.id,
          stage,
          retry: retryIndex,
          run: transientPersistenceRuns,
          message: currentAnalysis.transientMessage || null,
          wait_ms: transientPersistenceWaitMs,
          actions: transientPersistenceActions,
        });

        try {
          await runTransientPersistenceRecovery({
            actions: transientPersistenceActions,
            delayMs: transientPersistenceClickDelayMs,
            waitMs: transientPersistenceWaitMs,
            requestId: req.id,
            logger,
          });
          return true;
        } catch (persistenceErr) {
          logger?.warn?.('ocr.transient.persistence.error', {
            request_id: req.id,
            stage,
            retry: retryIndex,
            error: String(persistenceErr?.message || persistenceErr),
          });
          return false;
        }
      };
      const buildRunningPayload = (currentScreenshotPath, currentAnalysis, currentOutcome = 'running') => ({
        success: true,
        data: {
          screenshot_path: currentScreenshotPath ?? null,
          screenshot_url: null,
          ocr: currentAnalysis
            ? {
                text: currentAnalysis.rawText,
                normalized_text: currentAnalysis.normalizedText,
                plates: currentAnalysis.plates,
                error_message: currentAnalysis.errorMessage,
                error_code: currentAnalysis.errorCode || null,
                error_reason: currentAnalysis.errorReason || null,
                transient_message: currentAnalysis.transientMessage || null,
              }
            : null,
          transient_retry: {
            enabled: transientRetryEnabled,
            triggered: transientRetryTriggered,
            wait_ms: transientRetryWaitMs,
            max_retries: transientRetryMaxRetries,
            attempts: transientRetryChecks.length,
            persistence: {
              enabled: transientPersistenceEnabled,
              runs: transientPersistenceRuns,
              wait_ms: transientPersistenceWaitMs,
            },
            resolved: null,
            checks: transientRetryChecks,
          },
          outcome: currentOutcome,
        },
      });
      const persistRunningProgress = async (currentScreenshotPath, currentAnalysis, currentOutcome = 'running') => {
        try {
          await connection.query(
            "UPDATE placas_zero_km_requests SET response_payload=:payload, updated_at=NOW() WHERE id=:id AND status='running'",
            {
              id: req.id,
              payload: JSON.stringify(buildRunningPayload(currentScreenshotPath, currentAnalysis, currentOutcome)),
            }
          );
        } catch (persistErr) {
          logger?.warn?.('ocr.transient.persist_progress_error', {
            request_id: req.id,
            error: String(persistErr?.message || persistErr),
          });
        }
      };

      result = { lastScreenshotPath: null };
      for (let i = 0; i < templatePaths.length; i += 1) {
        const templatePath = templatePaths[i];
        const isLastTemplate = i === templatePaths.length - 1;
        const launchAppInThisStep = !shouldRunSeparateLogin && !agentCfg.loginBootstrapOnStart && i === 0;
        const templateHasSenha = await templateContainsSlot(templatePath, 'senha');

        if (shouldRunSeparateLogin && templateHasSenha) {
          logger?.info?.('replay.main.trim_login_section', {
            request_id: req.id,
            startAfterSlotName: 'senha',
            templatePath,
          });
        }

        logger?.info?.('replay.main.step', {
          request_id: req.id,
          step: i + 1,
          totalSteps: templatePaths.length,
          templatePath,
        });

        const stepResult = await replayTemplate({
          templatePath,
          data,
          screenshotsDir: agentCfg.screenshotsDir,
          maxDelayMs: agentCfg.maxDelayMs,
          speed: agentCfg.speed,
          replayText: agentCfg.replayText,
          replayVisualDebug: agentCfg.replayVisualDebug,
          replayVisualMs: agentCfg.replayVisualMs,
          replayVisualDotW: agentCfg.replayVisualDotW,
          replayVisualDotH: agentCfg.replayVisualDotH,
          replayVisualShowCard: agentCfg.replayVisualShowCard,
          preReplayWaitMs: i === 0 ? agentCfg.preReplayWaitMs : 0,
          postLoginWaitMs: agentCfg.postLoginWaitMs,
          cropWidth: agentCfg.screenshotCropW,
          cropHeight: agentCfg.screenshotCropH,
          stopAtScreenshot: isLastTemplate ? agentCfg.stopAtScreenshot : false,
          startAfterSlotName: shouldRunSeparateLogin && templateHasSenha ? 'senha' : '',
          requireRequiredSlots: multipleMainTemplates ? false : true,
          requireScreenshot: isLastTemplate,
          warnPasswordSlotMissing: !agentCfg.loginTemplatePath && !multipleMainTemplates && isLastTemplate,
          passwordInputMode: agentCfg.passwordInputMode,
          passwordTypeDelayMs: agentCfg.passwordTypeDelayMs,
          passwordBeforeEnterMs: agentCfg.passwordBeforeEnterMs,
          appExePath: launchAppInThisStep ? agentCfg.appExePath : '',
          appStartWaitMs: agentCfg.appStartWaitMs,
          autoEnterAfterClick: agentCfg.autoEnterAfterClick,
          autoEnterClickX: agentCfg.autoEnterClickX,
          autoEnterClickY: agentCfg.autoEnterClickY,
          autoEnterClickTolerance: agentCfg.autoEnterClickTolerance,
          autoEnterWaitBeforeMs: agentCfg.autoEnterWaitBeforeMs,
          autoEnterWaitAfterMs: agentCfg.autoEnterWaitAfterMs,
          appKillAfterScreenshot: isLastTemplate
            ? (deferAppKillUntilAfterRetries ? false : appKillAfterRequest)
            : false,
          logger,
        });

        if (stepResult?.lastScreenshotPath) {
          result = stepResult;
        }
      }

      let screenshotPath = result?.lastScreenshotPath ?? null;
      latestScreenshotPath = screenshotPath;
      logger?.info?.('replay.screenshot', { request_id: req.id, screenshotPath });

      if (agentCfg.ocrEnabled && screenshotPath) {
        analysis = await analyzeScreenshot(screenshotPath, {
          provider: agentCfg.ocrProvider || 'local',
          lang: agentCfg.ocrLang,
          maxPlates: agentCfg.ocrMaxPlates || 18,
          openAiApiKey: agentCfg.ocrOpenAiApiKey || '',
          openAiModel: agentCfg.ocrOpenAiModel || '',
          openAiBaseUrl: agentCfg.ocrOpenAiBaseUrl || '',
          openAiTimeoutMs: agentCfg.ocrOpenAiTimeoutMs || 30000,
          openAiFallbackLocal: Boolean(agentCfg.ocrOpenAiFallbackLocal),
          transientKeywords: agentCfg.transientKeywords,
          errorKeywords: agentCfg.errorKeywords,
          logger,
        });

        const shouldRetryAnalysis = (currentAnalysis) => {
          if (!transientRetryEnabled || !currentAnalysis) return false;
          const hasError = Boolean(currentAnalysis?.errorMessage);
          const hasPlates = Array.isArray(currentAnalysis?.plates) && currentAnalysis.plates.length > 0;
          return !hasError && !hasPlates;
        };

        if (shouldRetryAnalysis(analysis)) {
          transientRetryTriggered = true;
          const initialReason = analysis?.transientMessage ? 'transient_message' : 'no_result_yet';
          logger?.warn?.('ocr.transient.detected', {
            request_id: req.id,
            reason: initialReason,
            message: analysis.transientMessage || null,
            max_retries: transientRetryMaxRetries,
            wait_ms: transientRetryWaitMs,
          });
          let skipNextRetryDelay = await runTransientPersistenceIfNeeded({
            currentAnalysis: analysis,
            stage: 'initial',
            retryIndex: 0,
          });
          await persistRunningProgress(screenshotPath, analysis, 'waiting_retry');

          for (let retryIndex = 1; retryIndex <= transientRetryMaxRetries; retryIndex += 1) {
            if (skipNextRetryDelay) {
              skipNextRetryDelay = false;
            } else {
              await sleep(transientRetryWaitMs);
            }

            try {
              const retryScreenshotPath = await captureDesktopScreenshot({
                screenshotsDir: agentCfg.screenshotsDir,
                requestId: req.id,
                retryIndex,
                logger,
              });

              if (retryScreenshotPath) {
                screenshotPath = retryScreenshotPath;
                latestScreenshotPath = screenshotPath;
              }

              analysis = await analyzeScreenshot(screenshotPath, {
                provider: agentCfg.ocrProvider || 'local',
                lang: agentCfg.ocrLang,
                maxPlates: agentCfg.ocrMaxPlates || 18,
                openAiApiKey: agentCfg.ocrOpenAiApiKey || '',
                openAiModel: agentCfg.ocrOpenAiModel || '',
                openAiBaseUrl: agentCfg.ocrOpenAiBaseUrl || '',
                openAiTimeoutMs: agentCfg.ocrOpenAiTimeoutMs || 30000,
                openAiFallbackLocal: Boolean(agentCfg.ocrOpenAiFallbackLocal),
                transientKeywords: agentCfg.transientKeywords,
                errorKeywords: agentCfg.errorKeywords,
                logger,
              });

              const hasError = Boolean(analysis?.errorMessage);
              const hasPlates = Array.isArray(analysis?.plates) && analysis.plates.length > 0;
              const stillPending = !hasError && !hasPlates;

              const check = {
                retry: retryIndex,
                screenshot_path: screenshotPath,
                transient_message: analysis?.transientMessage || null,
                error_message: analysis?.errorMessage || null,
                plates: Array.isArray(analysis?.plates) ? analysis.plates : [],
                retry_reason: stillPending
                  ? (analysis?.transientMessage ? 'transient_message' : 'no_result_yet')
                  : null,
              };
              transientRetryChecks.push(check);

              logger?.info?.('ocr.transient.retry_result', {
                request_id: req.id,
                retry: retryIndex,
                transient_message: check.transient_message,
                plates_count: check.plates.length,
                error_message: check.error_message,
                retry_reason: check.retry_reason,
              });
              await persistRunningProgress(screenshotPath, analysis, stillPending ? 'waiting_retry' : 'running');

              if (!stillPending) {
                break;
              }

              skipNextRetryDelay = await runTransientPersistenceIfNeeded({
                currentAnalysis: analysis,
                stage: 'retry',
                retryIndex,
              });
            } catch (retryErr) {
              const retryErrorMessage = String(retryErr?.message || retryErr);
              transientRetryChecks.push({
                retry: retryIndex,
                screenshot_path: screenshotPath,
                transient_message: analysis?.transientMessage || null,
                error_message: retryErrorMessage,
                plates: [],
              });
              logger?.warn?.('ocr.transient.retry_error', {
                request_id: req.id,
                retry: retryIndex,
                error: retryErrorMessage,
              });
              await persistRunningProgress(screenshotPath, analysis, 'waiting_retry');
              skipNextRetryDelay = false;
            }
          }
        }
      }

      if (agentCfg.uploadEnabled && agentCfg.uploadUrl && screenshotPath) {
        uploadResult = await uploadScreenshot({
          url: agentCfg.uploadUrl,
          apiKey: agentCfg.uploadApiKey,
          requestId: req.id,
          filePath: screenshotPath,
          logger,
        });
      }

      const outcome = analysis
        ? (analysis?.errorMessage && (!analysis?.plates || analysis.plates.length === 0)
            ? 'error'
            : analysis?.plates && analysis.plates.length
              ? 'plates'
              : transientRetryTriggered &&
                  transientRetryChecks.length >= transientRetryMaxRetries &&
                  analysis?.transientMessage
                ? 'transient_timeout'
                : transientRetryTriggered &&
                    transientRetryChecks.length >= transientRetryMaxRetries
                  ? 'no_result_timeout'
                  : 'unknown')
        : (uploadResult ? 'uploaded' : 'unknown');

      const status =
        outcome === 'error' || outcome === 'transient_timeout' || outcome === 'no_result_timeout'
          ? 'failed'
          : (uploadResult || analysis)
            ? 'succeeded'
            : 'failed';

      const resolvedErrorMessage =
        outcome === 'error'
          ? (analysis?.errorCode
              ? `${analysis?.errorMessage || 'Erro detectado no modal'} (${analysis.errorCode})`
              : (analysis?.errorMessage || null))
          : null;
      const rawTextFallback = String(analysis?.rawText || '').trim();
      const rawTextSnippet = rawTextFallback ? rawTextFallback.slice(0, 600) : '';

      await connection.beginTransaction();
      await connection.query(
        "UPDATE placas_zero_km_requests SET status=:status, response_error=:err, response_payload=:payload, finished_at=NOW(), updated_at=NOW() WHERE id=:id",
        {
          id: req.id,
          status,
          err:
            outcome === 'error'
              ? (resolvedErrorMessage || rawTextSnippet || 'Erro detectado no modal')
              : outcome === 'transient_timeout'
                ? `Tela sem resposta após ${transientRetryMaxRetries} tentativa(s): ${analysis?.transientMessage || 'não está respondendo'}`
                : outcome === 'no_result_timeout'
                  ? `Sem placas/erro após ${transientRetryMaxRetries} nova(s) tentativa(s).`
                : null,
          payload: JSON.stringify({
            success: true,
            data: {
              screenshot_path: screenshotPath ?? null,
              screenshot_url: uploadResult?.screenshot_url ?? null,
              ocr: analysis
                ? {
                    text: analysis.rawText,
                    normalized_text: analysis.normalizedText,
                    plates: analysis.plates,
                    error_message: analysis.errorMessage,
                    error_code: analysis.errorCode || null,
                    error_reason: analysis.errorReason || null,
                    transient_message: analysis.transientMessage || null,
                  }
                : null,
              transient_retry: {
                enabled: transientRetryEnabled,
                triggered: transientRetryTriggered,
                wait_ms: transientRetryWaitMs,
                max_retries: transientRetryMaxRetries,
                attempts: transientRetryChecks.length,
                persistence: {
                  enabled: transientPersistenceEnabled,
                  runs: transientPersistenceRuns,
                  wait_ms: transientPersistenceWaitMs,
                },
                resolved: transientRetryTriggered
                  ? Boolean(analysis?.errorMessage) || (Array.isArray(analysis?.plates) && analysis.plates.length > 0)
                  : null,
                checks: transientRetryChecks,
              },
              outcome,
            },
          }),
        }
      );
      await releaseRunner(connection, req.id);
      await refreshBatchCounters(connection, req.batch_id);
      await connection.commit();
      logger?.info?.('request.done', {
        request_id: req.id,
        status,
        outcome,
      });
    } catch (err) {
      const errMsg = String(err?.message || err);
      try {
        await connection.beginTransaction();
        await connection.query(
          "UPDATE placas_zero_km_requests SET status='failed', response_error=:err, response_payload=NULL, finished_at=NOW(), updated_at=NOW() WHERE id=:id",
          { id: req.id, err: errMsg }
        );
        await releaseRunner(connection, req.id);
        await refreshBatchCounters(connection, req.batch_id);
        await connection.commit();
      } catch (innerErr) {
        logger?.error?.('request.failed.db_error', {
          request_id: req.id,
          error: String(innerErr?.message || innerErr),
        });
        await finalizeFailure(req.id, req.batch_id, errMsg);
      }
      logger?.error?.('request.failed', {
        request_id: req.id,
        error: errMsg,
      });
    } finally {
      stopHeartbeat();
      if (agentCfg.postResultCleanupEnabled && (latestScreenshotPath || analysis?.errorMessage)) {
        try {
          await runPostResultModalCleanup({
            points: agentCfg.postResultClickPoints,
            delayMs: agentCfg.postResultClickDelayMs,
            requestId: req.id,
            logger,
          });
        } catch (cleanupErr) {
          logger?.warn?.('modal.cleanup.error', {
            request_id: req.id,
            error: String(cleanupErr?.message || cleanupErr),
          });
        }
      }
      if (deferAppKillEnabled && deferredAppExePath) {
        await killAppProcessByExePath(deferredAppExePath, logger);
      }
    }

    return true;
  } finally {
    connection.release();
  }
}

async function main() {
  const agentCfg = loadAgentConfigFromEnv(process.env);
  const dbCfg = getMysqlConfigFromEnv(process.env);
  const pool = await createPool(process.env);
  const tokenUpdaterManager = createTokenUpdaterManager(process.env, logger);
  const basePollMs = toPositiveInt(agentCfg.pollIntervalMs, 5000);
  const maxDbRetryMs = toPositiveInt(process.env.AGENT_DB_MAX_RETRY_MS, 5 * 60 * 1000);
  const authRetryMs = toPositiveInt(process.env.AGENT_DB_AUTH_RETRY_MS, 15 * 60 * 1000);
  let dbRetryCount = 0;

  let shuttingDown = false;
  const stopTokenUpdaterOnSignal = async (signal) => {
    if (shuttingDown) return;
    shuttingDown = true;
    logger?.info?.('agent.signal', { signal });
    try {
      await tokenUpdaterManager.shutdown(`signal_${String(signal || '').toLowerCase() || 'unknown'}`);
    } finally {
      process.exit(0);
    }
  };
  process.once('SIGINT', () => {
    void stopTokenUpdaterOnSignal('SIGINT');
  });
  process.once('SIGTERM', () => {
    void stopTokenUpdaterOnSignal('SIGTERM');
  });

  logger?.info?.('agent.start', {
    template: agentCfg.templatePath,
    templates: agentCfg.templatePaths,
    loginTemplate: agentCfg.loginTemplatePath || null,
    loginBootstrapOnStart: Boolean(agentCfg.loginBootstrapOnStart),
    screenshotsDir: agentCfg.screenshotsDir,
    dbHost: dbCfg.host,
    dbPort: dbCfg.port,
    dbDatabase: dbCfg.database,
    dbUser: dbCfg.user,
    preflightEnabled: Boolean(agentCfg.preflightEnabled),
    preflightFocusExePath: agentCfg.preflightFocusExePath || null,
    preflightRequireFocus: Boolean(agentCfg.preflightRequireFocus),
    preflightOcrEnabled: Boolean(agentCfg.preflightOcrEnabled),
    preflightOcrProvider: agentCfg.preflightOcrProvider || 'local',
    preflightExpectedKeywords: agentCfg.preflightExpectedKeywords || [],
    preflightMinKeywordMatches: agentCfg.preflightMinKeywordMatches,
    preflightFailIfNotMatched: Boolean(agentCfg.preflightFailIfNotMatched),
    ocrProvider: agentCfg.ocrProvider || 'local',
    ocrModel: agentCfg.ocrProvider === 'openai' ? (agentCfg.ocrOpenAiModel || null) : null,
  });

  await runStartupLogin(agentCfg);

  if (agentCfg.ocrEnabled) {
    try {
      await warmupOcrWorker({
        provider: agentCfg.ocrProvider || 'local',
        lang: agentCfg.ocrLang,
        logger,
      });
      logger?.info?.('ocr.warmup.ready', {
        provider: agentCfg.ocrProvider || 'local',
        lang: agentCfg.ocrLang,
      });
    } catch (warmupErr) {
      logger?.warn?.('ocr.warmup.error', {
        error: String(warmupErr?.message || warmupErr),
      });
    }
  }

  // Loop: tenta processar 1 por vez; se não tiver, dorme e tenta de novo.
  for (;;) {
    try {
      const did = await processOne(pool, agentCfg, tokenUpdaterManager);
      dbRetryCount = 0;
      if (!did) {
        await tokenUpdaterManager.onQueueEmpty('queue_empty');
        await sleep(basePollMs);
      } else {
        await tokenUpdaterManager.onQueueBusy('request_processed');
        // processou 1 item; tenta o próximo “logo em seguida”
        await sleep(250);
      }
    } catch (err) {
      const errCode = String(err?.code || '');
      const errMsg = String(err?.message || err);
      if (isDbConnectionOrAuthError(err)) {
        dbRetryCount += 1;
        const expBackoffMs = Math.min(maxDbRetryMs, basePollMs * (2 ** Math.max(0, dbRetryCount - 1)));
        const retryMs = isDbAuthError(err) ? authRetryMs : expBackoffMs;
        logger?.error?.('agent.loop_db_error', {
          code: errCode || null,
          error: errMsg,
          retry_count: dbRetryCount,
          retry_in_ms: retryMs,
        });
        await sleep(retryMs);
      } else {
        dbRetryCount = 0;
        logger?.error?.('agent.loop_error', { code: errCode || null, error: errMsg });
        await sleep(basePollMs);
      }
    }
  }
}

main().catch((err) => {
  logger?.error?.('agent.fatal', { error: String(err?.message || err) });
  process.exit(1);
});
