import process from 'node:process';
import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import https from 'node:https';
import { chromium } from 'playwright';

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

function delay(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
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

async function getViewportBounds(page) {
  return page.evaluate(() => {
    const dpr = window.devicePixelRatio || 1;
    const innerLeft = window.screenX + (window.outerWidth - window.innerWidth) / 2;
    const innerTop = window.screenY + (window.outerHeight - window.innerHeight);
    return {
      left: Math.round(innerLeft * dpr),
      top: Math.round(innerTop * dpr),
      right: Math.round((innerLeft + window.innerWidth) * dpr),
      bottom: Math.round((innerTop + window.innerHeight) * dpr),
      dpr,
    };
  });
}

function globalToViewport(point, viewportBounds) {
  return {
    x: Math.round(point.x - viewportBounds.left),
    y: Math.round(point.y - viewportBounds.top),
  };
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

const FLOW_STEPS = [
  { kind: 'click', x: 352, y: 349, label: 'campo cpf' },
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

async function runFlowCycle() {
  if (!USER_CPF) {
    throw new Error('TOKEN_REFRESH_CPF nao definido.');
  }

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

  const context = await chromium.launchPersistentContext(BROWSER_USER_DATA_DIR, launchOptions);
  try {
    const pages = context.pages().filter((page) => !page.isClosed());
    const page = pages[0] || (await context.newPage());

    await page.bringToFront();
    await page.goto(TARGET_URL, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT_MS });
    await delay(1500);

    const viewportBounds = await getViewportBounds(page);
    console.log(
      `[token-refresh] Viewport absoluto: left=${viewportBounds.left} top=${viewportBounds.top} right=${viewportBounds.right} bottom=${viewportBounds.bottom} dpr=${viewportBounds.dpr}`
    );

    for (const step of FLOW_STEPS) {
      if (step.kind === 'wait') {
        console.log(`[token-refresh] Esperando ${step.ms}ms (${step.label}).`);
        await delay(step.ms);
        continue;
      }

      if (step.kind === 'click') {
        const target = globalToViewport(step, viewportBounds);
        console.log(
          `[token-refresh] Clique ${step.label}: global(${step.x},${step.y}) -> viewport(${target.x},${target.y})`
        );
        await page.mouse.click(target.x, target.y, { delay: 60 });
        continue;
      }

      if (step.kind === 'type') {
        const text = String(step.text()).trim();
        console.log(`[token-refresh] Digitando (${step.label}): ${text.length} caracteres.`);
        await page.keyboard.type(text, { delay: 80 });
      }
    }

    if (POST_LOGIN_WAIT_MS > 0) {
      await delay(POST_LOGIN_WAIT_MS);
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
    await context.close();
  }
}

async function main() {
  console.log('[token-refresh] Rotina iniciada.');
  console.log(
    `[token-refresh] Config: loop=${LOOP_ENABLED} interval_ms=${INTERVAL_MS} retry_ms=${RETRY_DELAY_MS} target=${TARGET_URL}`
  );

  while (true) {
    try {
      await runFlowCycle();
      if (!LOOP_ENABLED) break;
      console.log(`[token-refresh] Aguardando ${INTERVAL_MS}ms para novo ciclo...`);
      await delay(INTERVAL_MS);
    } catch (err) {
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
