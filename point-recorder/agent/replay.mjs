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

function parseCsvList(value) {
  if (value == null) return [];
  return String(value)
    .split(',')
    .map((part) => part.trim())
    .filter(Boolean);
}

function digitsOnly(value) {
  return String(value ?? '').replace(/\D+/g, '');
}

function detectCpfCnpjType(value) {
  const digits = digitsOnly(value);
  if (digits.length === 11) return { type: 'cpf', digits };
  if (digits.length === 14) return { type: 'cnpj', digits };
  return { type: 'invalid', digits };
}

async function ensureDir(dir) {
  await fs.mkdir(dir, { recursive: true });
}

function validateTemplate(events) {
  const slots = events
    .filter((event) => event?.type === 'slot_begin' && typeof event?.name === 'string')
    .map((event) => event.name);
  const screenshots = events.filter((event) => event?.type === 'screenshot').length;

  const required = ['chassi', 'cpf_cgc', 'nome'];
  const missing = required.filter((name) => !slots.includes(name));

  return {
    slots,
    screenshots,
    missing,
  };
}

function analyzeTemplateTiming(events) {
  let lastT = null;
  let maxDelta = 0;
  let timedEvents = 0;
  for (const event of events) {
    if (typeof event?.t !== 'number') continue;
    timedEvents += 1;
    if (lastT != null) {
      const delta = Math.max(0, event.t - lastT);
      if (delta > maxDelta) maxDelta = delta;
    }
    lastT = event.t;
  }
  return { timedEvents, maxDelta };
}

function buildReplayEvents(events, stopAtScreenshot) {
  if (!stopAtScreenshot) {
    return { replayEvents: events, droppedEvents: 0 };
  }
  const firstShot = events.findIndex((event) => event?.type === 'screenshot');
  if (firstShot < 0) {
    return { replayEvents: events, droppedEvents: 0 };
  }
  const replayEvents = events.slice(0, firstShot + 1);
  return {
    replayEvents,
    droppedEvents: Math.max(0, events.length - replayEvents.length),
  };
}

function trimTemplateAfterSlot(events, slotName) {
  if (!slotName) return { replayEvents: events, droppedBefore: 0 };
  const slotIndex = events.findIndex(
    (event) => event?.type === 'slot_begin' && String(event?.name || '') === String(slotName)
  );
  if (slotIndex < 0) {
    return { replayEvents: events, droppedBefore: 0 };
  }

  const startIndex = events.findIndex((event, idx) => {
    if (idx <= slotIndex) return false;
    return event?.type === 'mouse_down' || event?.type === 'slot_begin' || event?.type === 'screenshot';
  });

  if (startIndex < 0) {
    return { replayEvents: [], droppedBefore: events.length };
  }

  return {
    replayEvents: events.slice(startIndex),
    droppedBefore: startIndex,
  };
}

export async function replayTemplate({
  templatePath,
  data,
  screenshotsDir,
  maxDelayMs = 5000,
  speed = 1.0,
  replayText = false,
  preReplayWaitMs = 0,
  postLoginWaitMs = 0,
  cropWidth = 0,
  cropHeight = 0,
  stopAtScreenshot = true,
  startAfterSlotName = '',
  requireRequiredSlots = true,
  requireScreenshot = true,
  warnPasswordSlotMissing = true,
  logger = null,
}) {
  if (process.platform !== 'win32') {
    throw new Error('Replay suportado somente no Windows (necessita powershell.exe).');
  }

  logger?.info('replay.start', {
    templatePath,
    screenshotsDir,
    speed,
    replayText,
    preReplayWaitMs,
    postLoginWaitMs,
    hasCpf: Boolean(data?.cpf_cgc),
    hasNome: Boolean(data?.nome),
    hasChassi: Boolean(data?.chassi),
  });

  const absTemplate = path.resolve(process.cwd(), templatePath);
  const rawTemplate = await fs.readFile(absTemplate, 'utf8');
  const parsedTemplate = JSON.parse(rawTemplate);
  if (!Array.isArray(parsedTemplate)) {
    throw new Error('Template inválido: esperado array JSON.');
  }

  const templateMeta = validateTemplate(parsedTemplate);
  const trimmed = trimTemplateAfterSlot(parsedTemplate, startAfterSlotName);
  const built = buildReplayEvents(trimmed.replayEvents, stopAtScreenshot);
  const timingMeta = analyzeTemplateTiming(built.replayEvents);
  logger?.info('replay.template', {
    slots: templateMeta.slots,
    screenshots: templateMeta.screenshots,
    missing: templateMeta.missing,
    startAfterSlotName: startAfterSlotName || null,
    totalEvents: parsedTemplate.length,
    droppedBefore: trimmed.droppedBefore,
    replayEvents: built.replayEvents.length,
    droppedEvents: built.droppedEvents,
    stopAtScreenshot,
    timedEvents: timingMeta.timedEvents,
    maxDeltaMs: timingMeta.maxDelta,
    maxDelayMs,
  });
  if (maxDelayMs > 0 && timingMeta.maxDelta > maxDelayMs) {
    logger?.warn('replay.timing_warning', {
      maxDeltaMs: timingMeta.maxDelta,
      maxDelayMs,
      recommendation: 'Defina AGENT_MAX_DELAY_MS=0 para respeitar 100% o timing gravado.',
    });
  }
  if (templateMeta.missing.length > 0) {
    if (requireRequiredSlots) {
      throw new Error(`Template inválido: faltam slots obrigatórios (${templateMeta.missing.join(', ')}).`);
    }
    logger?.warn('replay.template_missing_required_slots_ignored', {
      templatePath,
      missing: templateMeta.missing,
    });
  }
  const doc = detectCpfCnpjType(data?.cpf_cgc);
  if (doc.type === 'invalid') {
    throw new Error(
      `cpf_cgc inválido para replay. Envie CPF com 11 dígitos ou CNPJ com 14 dígitos. Recebido: "${data?.cpf_cgc ?? ''}".`
    );
  }
  logger?.info('replay.document', { type: doc.type, digits: doc.digits.length });
  if (warnPasswordSlotMissing && data?.senha && !templateMeta.slots.includes('senha')) {
    logger?.warn('replay.password_slot_missing', {
      recommendation: 'Grave um slot F9 (senha) no template para garantir login automático.',
    });
  }
  if (requireScreenshot && templateMeta.screenshots < 1) {
    throw new Error('Template inválido: não possui evento de screenshot (F12).');
  }

  const outDir = path.resolve(process.cwd(), screenshotsDir || 'screenshots');
  await ensureDir(outDir);

  const tmpDataPath = path.join(
    os.tmpdir(),
    `placas0km-data-${Date.now()}-${Math.random().toString(36).slice(2)}.json`
  );
  const tmpTemplatePath = path.join(
    os.tmpdir(),
    `placas0km-template-${Date.now()}-${Math.random().toString(36).slice(2)}.json`
  );
  await fs.writeFile(tmpDataPath, JSON.stringify(data ?? {}), 'utf8');
  await fs.writeFile(tmpTemplatePath, JSON.stringify(built.replayEvents), 'utf8');

  const __dirname = path.dirname(fileURLToPath(import.meta.url));
  const psScript = path.join(__dirname, 'win-replay.ps1');

  const args = [
    '-NoProfile',
    '-ExecutionPolicy',
    'Bypass',
    '-File',
    psScript,
    '-TemplatePath',
    tmpTemplatePath,
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
    '-PreReplayWaitMs',
    String(preReplayWaitMs || 0),
    '-PostLoginWaitMs',
    String(postLoginWaitMs || 0),
    '-CropW',
    String(cropWidth || 0),
    '-CropH',
    String(cropHeight || 0),
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
  await fs.unlink(tmpTemplatePath).catch(() => {});

  let parsed = null;
  try {
    parsed = result ? JSON.parse(result) : null;
  } catch {
    parsed = null;
  }

  logger?.info('replay.done', { lastScreenshotPath: parsed?.lastScreenshotPath ?? null });

  return {
    lastScreenshotPath: parsed?.lastScreenshotPath ?? null,
  };
}

export function loadAgentConfigFromEnv(env) {
  const templatePath = env.AGENT_TEMPLATE_PATH || 'recordings/template.json';
  const templatePaths = parseCsvList(env.AGENT_TEMPLATE_PATHS);
  return {
    templatePath,
    templatePaths: templatePaths.length ? templatePaths : [templatePath],
    screenshotsDir: env.AGENT_SCREENSHOTS_DIR || 'screenshots',
    pollIntervalMs: Number(env.AGENT_POLL_INTERVAL_MS || 5000),
    maxDelayMs: Number(env.AGENT_MAX_DELAY_MS || 5000),
    speed: Number(env.AGENT_SPEED || 1.0),
    replayText: toBool(env.AGENT_REPLAY_TEXT, false),
    preReplayWaitMs: Number(env.AGENT_PRE_REPLAY_WAIT_MS || 0),
    postLoginWaitMs: Number(env.AGENT_POST_LOGIN_WAIT_MS || 0),
    loginTemplatePath: env.AGENT_LOGIN_TEMPLATE_PATH || '',
    betweenTemplatesWaitMs: Number(env.AGENT_BETWEEN_TEMPLATES_WAIT_MS || 0),
    screenshotCropW: Number(env.AGENT_SCREENSHOT_CROP_W || 0),
    screenshotCropH: Number(env.AGENT_SCREENSHOT_CROP_H || 0),
    stopAtScreenshot: toBool(env.AGENT_TEMPLATE_STOP_AT_SCREENSHOT, true),
    ocrEnabled: toBool(env.AGENT_OCR_ENABLED, true),
    ocrLang: env.AGENT_OCR_LANG || 'por',
    uploadEnabled: toBool(env.AGENT_UPLOAD_ENABLED, false),
    uploadUrl: env.AGENT_UPLOAD_URL || '',
    uploadApiKey: env.AGENT_UPLOAD_API_KEY || '',
    loginPassword: env.AGENT_LOGIN_PASSWORD || '',
  };
}
