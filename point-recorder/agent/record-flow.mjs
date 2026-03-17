import 'dotenv/config';
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { chromium } from 'playwright';

function toBool(value, fallback = false) {
  if (value == null) return fallback;
  const v = String(value).trim().toLowerCase();
  if (v === '1' || v === 'true' || v === 'yes' || v === 'y') return true;
  if (v === '0' || v === 'false' || v === 'no' || v === 'n') return false;
  return fallback;
}

function toPositiveInt(value, fallback) {
  const parsed = Number(value);
  if (!Number.isFinite(parsed) || parsed <= 0) return fallback;
  return Math.floor(parsed);
}

function sanitizeExecutablePath(value) {
  return String(value || '')
    .replace(/[\u200e\u200f\u202a-\u202e\u2066-\u2069]/g, '')
    .trim()
    .replace(/^"(.*)"$/, '$1');
}

function ensureDirForFile(filePath) {
  fs.mkdirSync(path.dirname(filePath), { recursive: true });
}

function getHook() {
  const require = createRequire(import.meta.url);
  let mod;
  try {
    mod = require('uiohook-napi');
  } catch (err) {
    console.error('Falha ao carregar uiohook-napi. Rode `npm install` dentro de point-recorder.');
    console.error(String(err?.message ?? err));
    process.exit(1);
  }

  const hook = mod.uIOhook || mod.uiohook || mod;
  return { hook };
}

const OUTPUT_PATH = path.resolve(
  process.cwd(),
  String(process.env.RECORD_FLOW_OUT || 'recordings/token-flow.jsonl').trim()
);
const TARGET_URL = String(
  process.env.RECORD_FLOW_TARGET_URL ||
    process.env.TOKEN_REFRESH_TARGET_URL ||
    'https://www.e-crvsp.sp.gov.br/'
).trim();

const USE_EXISTING_CHROME = toBool(process.env.TOKEN_REFRESH_USE_EXISTING_CHROME, false);
const CDP_URL = String(process.env.TOKEN_REFRESH_CDP_URL || 'http://127.0.0.1:9222').trim();
const NAV_TIMEOUT_MS = toPositiveInt(process.env.TOKEN_REFRESH_NAV_TIMEOUT_MS, 45 * 1000);
const BROWSER_CHANNEL = String(
  process.env.TOKEN_REFRESH_BROWSER_CHANNEL || process.env.CHROME_CHANNEL || 'chrome'
).trim();
const BROWSER_EXECUTABLE_PATH = sanitizeExecutablePath(
  process.env.TOKEN_REFRESH_BROWSER_EXECUTABLE_PATH || process.env.CHROME_EXECUTABLE_PATH || ''
);
const BROWSER_USER_DATA_DIR = String(
  process.env.TOKEN_REFRESH_USER_DATA_DIR ||
    path.resolve(process.cwd(), 'token-refresh-profile')
).trim();

const sessionStartedAt = Date.now();
let lastClick = null;
let browser = null;
let context = null;
let shouldCloseContext = false;
let createdPage = null;

ensureDirForFile(OUTPUT_PATH);
const stream = fs.createWriteStream(OUTPUT_PATH, { flags: 'a' });
stream.on('error', (err) => {
  console.error('Erro ao escrever no arquivo:', OUTPUT_PATH);
  console.error(String(err?.message ?? err));
  process.exit(1);
});

function baseRecord(type) {
  const now = Date.now();
  return {
    type,
    ts: new Date(now).toISOString(),
    t: now - sessionStartedAt,
  };
}

function writeRecord(record) {
  stream.write(`${JSON.stringify(record)}\n`);
}

function markLastClick(label) {
  if (!lastClick) {
    console.warn(`[record-flow] Nenhum clique anterior para marcar como ${label}.`);
    return;
  }

  const record = {
    ...baseRecord('mark'),
    label,
    x: lastClick.x,
    y: lastClick.y,
    button: lastClick.button,
  };

  writeRecord(record);
  console.log(`[record-flow] Marcado ${label}: x=${record.x} y=${record.y}`);
}

function isCtrlPressed(event) {
  if (typeof event?.ctrlKey === 'boolean') return event.ctrlKey;
  if (typeof event?.mask === 'number') return (event.mask & 2) === 2;
  return false;
}

function isFunctionKey(event, rawCode, keyCode) {
  return event?.rawcode === rawCode || event?.keycode === keyCode;
}

async function openChrome() {
  if (USE_EXISTING_CHROME) {
    console.log(`[record-flow] Conectando ao Chrome existente via CDP: ${CDP_URL}.`);
    browser = await chromium.connectOverCDP(CDP_URL);
    const contexts = browser.contexts();
    if (contexts.length > 0) {
      context = contexts[0];
    } else {
      console.warn(
        '[record-flow] Nenhum contexto existente encontrado no Chrome remoto; criando novo contexto (sem cookies existentes).'
      );
      context = await browser.newContext();
    }
  } else {
    const launchOptions = {
      headless: false,
      viewport: null,
      args: ['--start-maximized', '--new-window'],
      timeout: NAV_TIMEOUT_MS,
    };

    if (BROWSER_EXECUTABLE_PATH) {
      launchOptions.executablePath = BROWSER_EXECUTABLE_PATH;
    } else if (BROWSER_CHANNEL) {
      launchOptions.channel = BROWSER_CHANNEL;
    }

    console.log(
      `[record-flow] Iniciando Chrome (${BROWSER_EXECUTABLE_PATH ? `executablePath=${BROWSER_EXECUTABLE_PATH}` : `channel=${BROWSER_CHANNEL || 'padrao'}`}).`
    );
    context = await chromium.launchPersistentContext(BROWSER_USER_DATA_DIR, launchOptions);
    shouldCloseContext = true;
  }

  const pages = context.pages().filter((page) => !page.isClosed());
  const page = USE_EXISTING_CHROME ? await context.newPage() : pages[0] || (await context.newPage());
  createdPage = page;
  await page.bringToFront();
  await page.goto(TARGET_URL, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
  console.log(`[record-flow] Chrome pronto em: ${TARGET_URL}`);
}

async function shutdown() {
  if (shutdown.called) return;
  shutdown.called = true;

  try {
    const { hook } = getHook();
    hook.stop();
  } catch {
    // ignore
  }

  if (createdPage && !createdPage.isClosed()) {
    await createdPage.close();
  }
  if (shouldCloseContext && context) {
    await context.close();
  }
  if (!shouldCloseContext && browser && typeof browser.disconnect === 'function') {
    await browser.disconnect();
  }

  stream.end(() => process.exit(0));
}
shutdown.called = false;

async function main() {
  console.log('[record-flow] Iniciando gravacao de cliques.');
  console.log(`[record-flow] Saida: ${OUTPUT_PATH}`);
  console.log('[record-flow] F6 marca campo CPF | F7 marca campo PIN | Finalizar: Ctrl+Enter');
  await openChrome();

  const { hook } = getHook();

  hook.on('mousedown', (event) => {
    const record = {
      ...baseRecord('click'),
      x: event.x,
      y: event.y,
      button: event.button,
    };
    lastClick = record;
    writeRecord(record);
    console.log(`[record-flow] Click: x=${record.x} y=${record.y} button=${record.button}`);
  });

  hook.on('keydown', (event) => {
    const isEnter = event?.rawcode === 13 || event?.keycode === 28;
    if (isEnter && isCtrlPressed(event)) {
      console.log('[record-flow] Finalizando via Ctrl+Enter.');
      shutdown();
      return;
    }
    if (isFunctionKey(event, 117, 64)) {
      markLastClick('cpf');
      return;
    }
    if (isFunctionKey(event, 118, 65)) {
      markLastClick('pin');
    }
  });

  hook.start();
}

main().catch((err) => {
  console.error(`[record-flow] Erro: ${err.message}`);
  shutdown();
});

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
