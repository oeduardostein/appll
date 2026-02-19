import 'dotenv/config';

import process from 'node:process';
import fs from 'node:fs/promises';
import path from 'node:path';
import { spawn } from 'node:child_process';

import { createPool, getMysqlConfigFromEnv } from './db.mjs';
import { createLogger } from './logger.mjs';
import { loadAgentConfigFromEnv, replayTemplate } from './replay.mjs';
import { analyzeScreenshot } from './vision.mjs';
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

function psQuote(value) {
  return String(value ?? '').replace(/'/g, "''");
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
    nome: '',
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

async function processOne(pool, agentCfg) {
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
      nome: req.nome ?? '',
      chassi: req.chassi,
      senha: agentCfg.loginPassword ?? '',
    };

    let result;
    let analysis = null;
    let uploadResult = null;
    let deferAppKillEnabled = false;
    let deferredAppExePath = '';
    try {
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
      const deferAppKillUntilAfterRetries = transientRetryEnabled && appKillAfterRequest;
      deferAppKillEnabled = deferAppKillUntilAfterRetries;
      deferredAppExePath = agentCfg.appExePath || '';
      const transientRetryChecks = [];
      let transientRetryTriggered = false;
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
                transient_message: currentAnalysis.transientMessage || null,
              }
            : null,
          transient_retry: {
            enabled: transientRetryEnabled,
            triggered: transientRetryTriggered,
            wait_ms: transientRetryWaitMs,
            max_retries: transientRetryMaxRetries,
            attempts: transientRetryChecks.length,
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
      logger?.info?.('replay.screenshot', { request_id: req.id, screenshotPath });

      if (agentCfg.ocrEnabled && screenshotPath) {
        analysis = await analyzeScreenshot(screenshotPath, {
          lang: agentCfg.ocrLang,
          transientKeywords: agentCfg.transientKeywords,
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
          await persistRunningProgress(screenshotPath, analysis, 'waiting_retry');

          for (let retryIndex = 1; retryIndex <= transientRetryMaxRetries; retryIndex += 1) {
            await sleep(transientRetryWaitMs);

            try {
              const retryScreenshotPath = await captureDesktopScreenshot({
                screenshotsDir: agentCfg.screenshotsDir,
                requestId: req.id,
                retryIndex,
                logger,
              });

              if (retryScreenshotPath) {
                screenshotPath = retryScreenshotPath;
              }

              analysis = await analyzeScreenshot(screenshotPath, {
                lang: agentCfg.ocrLang,
                transientKeywords: agentCfg.transientKeywords,
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

      await connection.beginTransaction();
      await connection.query(
        "UPDATE placas_zero_km_requests SET status=:status, response_error=:err, response_payload=:payload, finished_at=NOW(), updated_at=NOW() WHERE id=:id",
        {
          id: req.id,
          status,
          err:
            outcome === 'error'
              ? (String(analysis?.rawText || '').trim() || analysis?.errorMessage || 'Erro detectado no modal')
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
                    transient_message: analysis.transientMessage || null,
                  }
                : null,
              transient_retry: {
                enabled: transientRetryEnabled,
                triggered: transientRetryTriggered,
                wait_ms: transientRetryWaitMs,
                max_retries: transientRetryMaxRetries,
                attempts: transientRetryChecks.length,
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
  const basePollMs = toPositiveInt(agentCfg.pollIntervalMs, 5000);
  const maxDbRetryMs = toPositiveInt(process.env.AGENT_DB_MAX_RETRY_MS, 5 * 60 * 1000);
  const authRetryMs = toPositiveInt(process.env.AGENT_DB_AUTH_RETRY_MS, 15 * 60 * 1000);
  let dbRetryCount = 0;

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
  });

  await runStartupLogin(agentCfg);

  // Loop: tenta processar 1 por vez; se não tiver, dorme e tenta de novo.
  for (;;) {
    try {
      const did = await processOne(pool, agentCfg);
      dbRetryCount = 0;
      if (!did) {
        await sleep(basePollMs);
      } else {
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
