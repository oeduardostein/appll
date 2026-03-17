import 'dotenv/config';
import process from 'node:process';
import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import https from 'node:https';
import { chromium } from 'playwright';
import robot from 'robotjs';

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

function sanitizeExecutablePath(value) {
  return String(value || '')
    .replace(/[\u200e\u200f\u202a-\u202e\u2066-\u2069]/g, '')
    .trim()
    .replace(/^"(.*)"$/, '$1');
}

let stopRequested = false;

function requestStop(signal) {
  if (stopRequested) return;
  stopRequested = true;
  console.log(`[token-refresh] Sinal ${signal} recebido. Encerrando...`);
}

function delay(ms) {
  const step = 200;
  let remaining = Number(ms) || 0;
  return new Promise((resolve) => {
    const tick = () => {
      if (stopRequested || remaining <= 0) {
        resolve();
        return;
      }
      const slice = Math.min(step, remaining);
      remaining -= slice;
      setTimeout(tick, slice);
    };
    tick();
  });
}

function ensureDir(dirPath) {
  if (!dirPath) return;
  if (!fs.existsSync(dirPath)) {
    fs.mkdirSync(dirPath, { recursive: true });
  }
}

function postJson(url, payload) {
  return new Promise((resolve, reject) => {
    let target;
    try {
      target = new URL(url);
    } catch (err) {
      reject(new Error(`TOKEN_REFRESH_UPDATE_URL invalida: ${err.message}`));
      return;
    }

    const data = JSON.stringify(payload);
    const isHttps = target.protocol === 'https:';
    const client = isHttps ? https : http;

    const req = client.request(
      {
        hostname: target.hostname,
        port: target.port || (isHttps ? 443 : 80),
        method: 'POST',
        path: target.pathname + target.search,
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(data),
        },
      },
      (res) => {
        let body = '';
        res.on('data', (chunk) => {
          body += chunk;
        });
        res.on('end', () => {
          resolve({
            status: res.statusCode ?? 0,
            body,
          });
        });
      }
    );

    req.on('error', reject);
    req.write(data);
    req.end();
  });
}

function nativeClick(x, y) {
  robot.moveMouseSmooth(x, y);
  robot.mouseClick('left', false);
}

function nativeType(text) {
  if (!text) return;
  robot.typeString(text);
}

function extractSessionToken(cookies) {
  const jsession = cookies.find((cookie) => cookie.name === 'JSESSIONID' && cookie.value);
  if (jsession?.value) return jsession.value;
  const php = cookies.find((cookie) => cookie.name === 'PHPSESSID' && cookie.value);
  if (php?.value) return php.value;
  return null;
}

const TARGET_URL = String(
  process.env.TOKEN_REFRESH_TARGET_URL || 'https://www.e-crvsp.sp.gov.br/'
).trim();
const UPDATE_URL = String(
  process.env.TOKEN_REFRESH_UPDATE_URL ||
    process.env.TOKEN_UPDATE_URL ||
    'https://applldespachante.skalacode.com/api/update-token'
).trim();
const USER_CPF = String(process.env.TOKEN_REFRESH_CPF || '').trim();
const PIN_CODE = String(process.env.TOKEN_REFRESH_PIN || '1234').trim();
const LOOP_ENABLED = toBool(process.env.TOKEN_REFRESH_LOOP_ENABLED, true);
const INTERVAL_MS = toPositiveInt(process.env.TOKEN_REFRESH_INTERVAL_MS, 10 * 60 * 1000);
const RETRY_DELAY_MS = toPositiveInt(process.env.TOKEN_REFRESH_RETRY_DELAY_MS, 30 * 1000);
const NAV_TIMEOUT_MS = toPositiveInt(process.env.TOKEN_REFRESH_NAV_TIMEOUT_MS, 45 * 1000);
const POST_LOGIN_WAIT_MS = toNonNegativeInt(process.env.TOKEN_REFRESH_POST_LOGIN_WAIT_MS, 2000);
const USE_EXISTING_CHROME = toBool(process.env.TOKEN_REFRESH_USE_EXISTING_CHROME, false);
const CDP_URL = String(process.env.TOKEN_REFRESH_CDP_URL || 'http://127.0.0.1:9222').trim();
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

const DEFAULT_FLOW_FILE = path.resolve(process.cwd(), 'recordings/token-flow.jsonl');
const FLOW_FILE = String(process.env.TOKEN_REFRESH_FLOW_FILE || DEFAULT_FLOW_FILE).trim();
const FLOW_FILE_ENABLED = toBool(process.env.TOKEN_REFRESH_FLOW_FILE_ENABLED, true);

const FLOW_STEPS = [
  { kind: 'click', x: 1510, y: 115, label: 'clique inicial' },
  { kind: 'click', x: 274, y: 467, label: 'campo cpf' },
  { kind: 'type', text: () => USER_CPF, label: 'digitar cpf' },
  { kind: 'wait', ms: 2000, label: 'aguardo apos cpf' },
  { kind: 'click', x: 407, y: 386, label: 'botao continuar' },
  { kind: 'wait', ms: 5000, label: 'aguardo pos continuar' },
  { kind: 'click', x: 1050, y: 654, label: 'acao 1' },
  { kind: 'wait', ms: 5000, label: 'aguardo 5s' },
  { kind: 'click', x: 887, y: 358, label: 'acao 2' },
  { kind: 'wait', ms: 2000, label: 'aguardo 2s' },
  { kind: 'click', x: 772, y: 402, label: 'campo pin' },
  { kind: 'wait', ms: 1000, label: 'aguardo antes do pin' },
  { kind: 'type', text: () => PIN_CODE, label: 'digitar pin' },
  { kind: 'wait', ms: 15000, label: 'aguardo 15s' },
  { kind: 'click', x: 936, y: 212, label: 'acao 3' },
  { kind: 'wait', ms: 2000, label: 'aguardo 2s' },
  { kind: 'click', x: 681, y: 646, label: 'acao final' },
];

function parseFlowFileContent(content) {
  const trimmed = String(content || '').trim();
  if (!trimmed) return [];
  if (trimmed.startsWith('[')) {
    const parsed = JSON.parse(trimmed);
    return Array.isArray(parsed) ? parsed : [];
  }
  return trimmed
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)
    .map((line) => JSON.parse(line));
}

function buildStepsFromRecords(records) {
  const events = records.filter(
    (rec) =>
      (rec?.type === 'click' || rec?.type === 'mark') &&
      Number.isFinite(rec?.x) &&
      Number.isFinite(rec?.y)
  );

  let prevT = 0;
  const steps = [];

  for (const event of events) {
    if (Number.isFinite(event.t)) {
      const delta = Math.max(0, event.t - prevT);
      if (delta > 0) {
        steps.push({ kind: 'wait', ms: delta, label: `aguardo ${delta}ms` });
      }
      prevT = event.t;
    }

    if (event.type === 'click') {
      steps.push({ kind: 'click', x: event.x, y: event.y, label: 'click gravado' });
      continue;
    }

    if (event.type === 'mark') {
      if (event.label === 'cpf') {
        steps.push({ kind: 'type', text: () => USER_CPF, label: 'digitar cpf' });
      } else if (event.label === 'pin') {
        steps.push({ kind: 'type', text: () => PIN_CODE, label: 'digitar pin' });
      }
    }
  }

  return steps;
}

function resolveFlowSteps() {
  if (!FLOW_FILE_ENABLED) {
    console.log('[token-refresh] Fluxo gravado desativado; usando passos fixos.');
    return FLOW_STEPS;
  }

  if (!fs.existsSync(FLOW_FILE)) {
    if (process.env.TOKEN_REFRESH_FLOW_FILE) {
      throw new Error(`Arquivo de fluxo nao encontrado: ${FLOW_FILE}`);
    }
    console.log('[token-refresh] Fluxo gravado nao encontrado; usando passos fixos.');
    return FLOW_STEPS;
  }

  const content = fs.readFileSync(FLOW_FILE, 'utf8');
  const records = parseFlowFileContent(content);
  const steps = buildStepsFromRecords(records);

  if (!steps.length) {
    throw new Error(`Arquivo de fluxo vazio/sem passos validos: ${FLOW_FILE}`);
  }

  console.log(`[token-refresh] Usando fluxo gravado: ${FLOW_FILE} (passos=${steps.length}).`);
  return steps;
}

async function runFlowCycle() {
  if (!USER_CPF) {
    throw new Error('TOKEN_REFRESH_CPF nao definido.');
  }

  const flowSteps = resolveFlowSteps();

  let context;
  let browser;
  let shouldCloseContext = false;
  let createdPage;

  if (USE_EXISTING_CHROME) {
    console.log(`[token-refresh] Usando sessao do Chrome existente via CDP: ${CDP_URL}.`);
    browser = await chromium.connectOverCDP(CDP_URL);
    const contexts = browser.contexts();
    if (contexts.length > 0) {
      context = contexts[0];
    } else {
      console.warn(
        '[token-refresh] Nenhum contexto existente encontrado no Chrome remoto; criando novo contexto (sem cookies existentes).'
      );
      context = await browser.newContext();
    }
  } else {
    ensureDir(BROWSER_USER_DATA_DIR);

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
      `[token-refresh] Iniciando Chrome (${BROWSER_EXECUTABLE_PATH ? `executablePath=${BROWSER_EXECUTABLE_PATH}` : `channel=${BROWSER_CHANNEL || 'padrao'}`}).`
    );

    context = await chromium.launchPersistentContext(BROWSER_USER_DATA_DIR, launchOptions);
    shouldCloseContext = true;
  }

  try {
    const pages = context.pages().filter((page) => !page.isClosed());
    const page = USE_EXISTING_CHROME ? await context.newPage() : pages[0] || (await context.newPage());
    createdPage = page;

    await page.bringToFront();
    await page.goto(TARGET_URL, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await delay(1500);
    robot.setMouseDelay(60);
    robot.setKeyboardDelay(80);

    for (const step of flowSteps) {
      if (stopRequested) {
        console.log('[token-refresh] Encerrando ciclo por solicitacao.');
        return;
      }
      if (step.kind === 'wait') {
        console.log(`[token-refresh] Esperando ${step.ms}ms (${step.label}).`);
        await delay(step.ms);
        continue;
      }

      if (step.kind === 'click') {
        console.log(`[token-refresh] Clique ${step.label}: screen(${step.x},${step.y})`);
        nativeClick(step.x, step.y);
        continue;
      }

      if (step.kind === 'type') {
        const text = String(step.text()).trim();
        console.log(`[token-refresh] Digitando (${step.label}): ${text.length} caracteres.`);
        nativeType(text);
      }
    }

    if (stopRequested) {
      console.log('[token-refresh] Encerrando ciclo por solicitacao.');
      return;
    }

    if (POST_LOGIN_WAIT_MS > 0) {
      await delay(POST_LOGIN_WAIT_MS);
    }

    if (stopRequested) {
      console.log('[token-refresh] Encerrando ciclo por solicitacao.');
      return;
    }

    const cookies = await context.cookies();
    const token = extractSessionToken(cookies);
    if (!token) {
      throw new Error('Nao foi encontrado JSESSIONID/PHPSESSID apos o fluxo.');
    }

    const response = await postJson(UPDATE_URL, { token });
    if (response.status < 200 || response.status >= 300) {
      throw new Error(
        `Falha ao atualizar token via API (status=${response.status}). Body=${response.body || '(vazio)'}`
      );
    }

    console.log(`[token-refresh] Token atualizado com sucesso (status=${response.status}).`);
  } finally {
    if (createdPage && !createdPage.isClosed()) {
      await createdPage.close();
    }
    if (shouldCloseContext && context) {
      await context.close();
    }
    if (!shouldCloseContext && browser && typeof browser.disconnect === 'function') {
      await browser.disconnect();
    }
  }
}

async function main() {
  console.log('[token-refresh] Rotina iniciada.');
  console.log(
    `[token-refresh] Config: loop=${LOOP_ENABLED} interval_ms=${INTERVAL_MS} retry_ms=${RETRY_DELAY_MS} target=${TARGET_URL} input=nativo chrome_existente=${USE_EXISTING_CHROME} cdp_url=${CDP_URL} flow_file=${FLOW_FILE_ENABLED ? FLOW_FILE : 'desativado'}`
  );

  while (true) {
    if (stopRequested) break;
    try {
      await runFlowCycle();
      if (!LOOP_ENABLED || stopRequested) break;
      console.log(`[token-refresh] Aguardando ${INTERVAL_MS}ms para novo ciclo...`);
      await delay(INTERVAL_MS);
    } catch (err) {
      if (stopRequested) break;
      console.error(`[token-refresh] Erro no ciclo: ${err.message}`);
      if (!LOOP_ENABLED) {
        throw err;
      }
      console.log(`[token-refresh] Nova tentativa em ${RETRY_DELAY_MS}ms...`);
      await delay(RETRY_DELAY_MS);
    }
  }
}

main().catch((err) => {
  console.error('[token-refresh] Erro fatal:', err);
  process.exit(1);
});

process.on('SIGINT', () => requestStop('SIGINT'));
process.on('SIGTERM', () => requestStop('SIGTERM'));
