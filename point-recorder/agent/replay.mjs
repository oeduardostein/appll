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

function parseJsonMaybeBom(rawText) {
  const normalized =
    typeof rawText === 'string' && rawText.charCodeAt(0) === 0xfeff
      ? rawText.slice(1)
      : rawText;
  return JSON.parse(normalized);
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

function collectClickPoints(events) {
  const points = [];
  for (let i = 0; i < events.length; i += 1) {
    const event = events[i];
    if (!event || typeof event !== 'object') continue;
    const type = String(event.type || '');
    if (type !== 'mouse_down' && type !== 'slot_begin') continue;
    if (typeof event.x !== 'number' || typeof event.y !== 'number') continue;
    points.push({
      step: points.length + 1,
      eventIndex: i,
      type,
      name: type === 'slot_begin' ? String(event.name || '') : '',
      x: Math.round(event.x),
      y: Math.round(event.y),
    });
  }
  return points;
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
  replayVisualDebug = false,
  replayVisualMs = 180,
  replayVisualDotW = 12,
  replayVisualDotH = 12,
  replayVisualShowCard = true,
  preReplayWaitMs = 0,
  postLoginWaitMs = 0,
  cropWidth = 0,
  cropHeight = 0,
  stopAtScreenshot = true,
  startAfterSlotName = '',
  requireRequiredSlots = true,
  requireScreenshot = true,
  warnPasswordSlotMissing = true,
  passwordInputMode = 'paste',
  passwordTypeDelayMs = 120,
  passwordBeforeEnterMs = 350,
  appExePath = '',
  appStartWaitMs = 7000,
  autoEnterAfterClick = false,
  autoEnterClickX = 0,
  autoEnterClickY = 0,
  autoEnterClickTolerance = 3,
  autoEnterWaitBeforeMs = 2000,
  autoEnterWaitAfterMs = 2000,
  appKillAfterScreenshot = true,
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
    replayVisualDebug,
    replayVisualMs,
    replayVisualDotW,
    replayVisualDotH,
    replayVisualShowCard,
    preReplayWaitMs,
    postLoginWaitMs,
    hasCpf: Boolean(data?.cpf_cgc),
    hasNome: Boolean(data?.nome),
    hasChassi: Boolean(data?.chassi),
    passwordInputMode,
    passwordTypeDelayMs,
    passwordBeforeEnterMs,
    appExePath: appExePath || null,
    appStartWaitMs,
    autoEnterAfterClick,
    autoEnterClickX,
    autoEnterClickY,
    autoEnterClickTolerance,
    autoEnterWaitBeforeMs,
    autoEnterWaitAfterMs,
    appKillAfterScreenshot,
  });

  const absTemplate = path.resolve(process.cwd(), templatePath);
  const rawTemplate = await fs.readFile(absTemplate, 'utf8');
  const parsedTemplate = parseJsonMaybeBom(rawTemplate);
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
  if (replayVisualDebug) {
    const clickPoints = collectClickPoints(built.replayEvents);
    logger?.debug('replay.click_points', {
      templatePath,
      pointsCount: clickPoints.length,
      points: clickPoints,
    });
  }
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
    '-VisualDebug',
    replayVisualDebug ? 'true' : 'false',
    '-VisualDebugMs',
    String(replayVisualMs || 0),
    '-VisualDebugDotW',
    String(replayVisualDotW || 12),
    '-VisualDebugDotH',
    String(replayVisualDotH || 12),
    '-VisualDebugShowCard',
    replayVisualShowCard ? 'true' : 'false',
    '-PreReplayWaitMs',
    String(preReplayWaitMs || 0),
    '-PostLoginWaitMs',
    String(postLoginWaitMs || 0),
    '-CropW',
    String(cropWidth || 0),
    '-CropH',
    String(cropHeight || 0),
    '-PasswordInputMode',
    String(passwordInputMode || 'paste'),
    '-PasswordTypeDelayMs',
    String(passwordTypeDelayMs || 120),
    '-PasswordBeforeEnterMs',
    String(passwordBeforeEnterMs || 350),
    '-AppExePath',
    String(appExePath || ''),
    '-AppStartWaitMs',
    String(appStartWaitMs || 7000),
    '-AutoEnterAfterClick',
    autoEnterAfterClick ? 'true' : 'false',
    '-AutoEnterClickX',
    String(autoEnterClickX || 0),
    '-AutoEnterClickY',
    String(autoEnterClickY || 0),
    '-AutoEnterClickTolerance',
    String(autoEnterClickTolerance || 3),
    '-AutoEnterWaitBeforeMs',
    String(autoEnterWaitBeforeMs || 2000),
    '-AutoEnterWaitAfterMs',
    String(autoEnterWaitAfterMs || 2000),
    '-AppKillAfterScreenshot',
    appKillAfterScreenshot ? 'true' : 'false',
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
  const passwordInputModeRaw = String(env.AGENT_PASSWORD_INPUT_MODE || 'paste').trim().toLowerCase();
  const passwordInputMode = passwordInputModeRaw === 'type' ? 'type' : 'paste';
  return {
    templatePath,
    templatePaths: templatePaths.length ? templatePaths : [templatePath],
    screenshotsDir: env.AGENT_SCREENSHOTS_DIR || 'screenshots',
    pollIntervalMs: Number(env.AGENT_POLL_INTERVAL_MS || 5000),
    maxDelayMs: Number(env.AGENT_MAX_DELAY_MS || 5000),
    speed: Number(env.AGENT_SPEED || 1.0),
    replayText: toBool(env.AGENT_REPLAY_TEXT, false),
    replayVisualDebug: toBool(env.AGENT_REPLAY_VISUAL_DEBUG, false),
    replayVisualMs: Number(env.AGENT_REPLAY_VISUAL_MS || 180),
    replayVisualDotW: Number(env.AGENT_REPLAY_VISUAL_DOT_W || 12),
    replayVisualDotH: Number(env.AGENT_REPLAY_VISUAL_DOT_H || 12),
    replayVisualShowCard: toBool(env.AGENT_REPLAY_VISUAL_SHOW_CARD, true),
    preReplayWaitMs: Number(env.AGENT_PRE_REPLAY_WAIT_MS || 0),
    postLoginWaitMs: Number(env.AGENT_POST_LOGIN_WAIT_MS || 0),
    loginTemplatePath: env.AGENT_LOGIN_TEMPLATE_PATH || '',
    loginBootstrapOnStart: toBool(env.AGENT_LOGIN_BOOTSTRAP_ON_START, true),
    betweenTemplatesWaitMs: Number(env.AGENT_BETWEEN_TEMPLATES_WAIT_MS || 0),
    screenshotCropW: Number(env.AGENT_SCREENSHOT_CROP_W || 0),
    screenshotCropH: Number(env.AGENT_SCREENSHOT_CROP_H || 0),
    stopAtScreenshot: toBool(env.AGENT_TEMPLATE_STOP_AT_SCREENSHOT, true),
    ocrEnabled: toBool(env.AGENT_OCR_ENABLED, true),
    ocrLang: env.AGENT_OCR_LANG || 'por',
    transientRetryEnabled: toBool(env.AGENT_TRANSIENT_RETRY_ENABLED, true),
    transientRetryWaitMs: Number(env.AGENT_TRANSIENT_RETRY_WAIT_MS || 8000),
    transientRetryMaxRetries: Number(env.AGENT_TRANSIENT_RETRY_MAX_RETRIES || 6),
    transientKeywords: parseCsvList(env.AGENT_TRANSIENT_KEYWORDS),
    uploadEnabled: toBool(env.AGENT_UPLOAD_ENABLED, false),
    uploadUrl: env.AGENT_UPLOAD_URL || '',
    uploadApiKey: env.AGENT_UPLOAD_API_KEY || '',
    loginPassword: env.AGENT_LOGIN_PASSWORD || '',
    passwordInputMode,
    passwordTypeDelayMs: Number(env.AGENT_PASSWORD_TYPE_DELAY_MS || 120),
    passwordBeforeEnterMs: Number(env.AGENT_PASSWORD_BEFORE_ENTER_MS || 350),
    appExePath: env.AGENT_APP_EXE_PATH || '',
    appStartWaitMs: Number(env.AGENT_APP_START_WAIT_MS || 7000),
    autoEnterAfterClick: toBool(env.AGENT_AUTO_ENTER_AFTER_CLICK, false),
    autoEnterClickX: Number(env.AGENT_AUTO_ENTER_CLICK_X || 0),
    autoEnterClickY: Number(env.AGENT_AUTO_ENTER_CLICK_Y || 0),
    autoEnterClickTolerance: Number(env.AGENT_AUTO_ENTER_CLICK_TOLERANCE || 3),
    autoEnterWaitBeforeMs: Number(env.AGENT_AUTO_ENTER_WAIT_BEFORE_MS || 2000),
    autoEnterWaitAfterMs: Number(env.AGENT_AUTO_ENTER_WAIT_AFTER_MS || 2000),
    appKillAfterScreenshot: toBool(env.AGENT_APP_KILL_AFTER_SCREENSHOT, true),
  };
}
