import fs from 'node:fs/promises';
import path from 'node:path';
import os from 'node:os';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function toBool(value, defaultValue = false) {
  if (value == null) return defaultValue;
  const v = String(value).trim().toLowerCase();
  if (v === '1' || v === 'true' || v === 'yes' || v === 'y') return true;
  if (v === '0' || v === 'false' || v === 'no' || v === 'n') return false;
  return defaultValue;
}

async function ensureDir(dir) {
  await fs.mkdir(dir, { recursive: true });
}

export async function replayTemplate({
  templatePath,
  data,
  screenshotsDir,
  maxDelayMs = 5000,
  speed = 1.0,
  replayText = false,
}) {
  if (process.platform !== 'win32') {
    throw new Error('Replay suportado somente no Windows (necessita powershell.exe).');
  }

  const absTemplate = path.resolve(process.cwd(), templatePath);
  const outDir = path.resolve(process.cwd(), screenshotsDir || 'screenshots');
  await ensureDir(outDir);

  const tmpDataPath = path.join(
    os.tmpdir(),
    `placas0km-data-${Date.now()}-${Math.random().toString(36).slice(2)}.json`
  );
  await fs.writeFile(tmpDataPath, JSON.stringify(data ?? {}), 'utf8');

  const __dirname = path.dirname(fileURLToPath(import.meta.url));
  const psScript = path.join(__dirname, 'win-replay.ps1');

  const args = [
    '-NoProfile',
    '-ExecutionPolicy',
    'Bypass',
    '-File',
    psScript,
    '-TemplatePath',
    absTemplate,
    '-DataPath',
    tmpDataPath,
    '-ScreenshotsDir',
    outDir,
    '-MaxDelayMs',
    String(maxDelayMs),
    '-Speed',
    String(speed),
    '-ReplayText',
    replayText ? 'true' : 'false',
  ];

  const result = await new Promise((resolve, reject) => {
    const child = spawn('powershell.exe', args, { windowsHide: true });
    let stdout = '';
    let stderr = '';
    child.stdout.on('data', (d) => (stdout += d.toString()));
    child.stderr.on('data', (d) => (stderr += d.toString()));
    child.on('error', reject);
    child.on('close', (code) => {
      if (code !== 0) {
        reject(new Error(stderr || `powershell falhou (code ${code}).`));
        return;
      }
      resolve(stdout.trim());
    });
  });

  await fs.unlink(tmpDataPath).catch(() => {});

  let parsed = null;
  try {
    parsed = result ? JSON.parse(result) : null;
  } catch {
    parsed = null;
  }

  return {
    lastScreenshotPath: parsed?.lastScreenshotPath ?? null,
  };
}

export function loadAgentConfigFromEnv(env) {
  return {
    templatePath: env.AGENT_TEMPLATE_PATH || 'recordings/template.json',
    screenshotsDir: env.AGENT_SCREENSHOTS_DIR || 'screenshots',
    pollIntervalMs: Number(env.AGENT_POLL_INTERVAL_MS || 5000),
    maxDelayMs: Number(env.AGENT_MAX_DELAY_MS || 5000),
    speed: Number(env.AGENT_SPEED || 1.0),
    replayText: toBool(env.AGENT_REPLAY_TEXT, false),
    ocrEnabled: toBool(env.AGENT_OCR_ENABLED, true),
    ocrLang: env.AGENT_OCR_LANG || 'por',
    uploadEnabled: toBool(env.AGENT_UPLOAD_ENABLED, false),
    uploadUrl: env.AGENT_UPLOAD_URL || '',
    uploadApiKey: env.AGENT_UPLOAD_API_KEY || '',
  };
}
