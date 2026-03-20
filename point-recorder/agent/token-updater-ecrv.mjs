/**
 * Token Updater para E-CRV SP usando Puppeteer
 * Mantém a sessão ativa recarregando a página periodicamente
 *
 * Uso:
 *   node agent/token-updater-ecrv.mjs
 */

import puppeteer from 'puppeteer';
import mysql from 'mysql2/promise';
import process from 'node:process';
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { setTimeout as sleep } from 'node:timers/promises';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// Configuração do banco de dados
const dbConfig = {
  host: process.env.DB_HOST || '193.203.175.152',
  port: parseInt(process.env.DB_PORT || '3306'),
  database: process.env.DB_DATABASE || 'u365250089_appll',
  user: process.env.DB_USERNAME || 'u365250089_appll',
  password: process.env.DB_PASSWORD || 'Skala@2025$',
};

const ECRV_URL = 'https://www.e-crvsp.sp.gov.br/';
const ECRV_CPF = process.env.ECRV_CPF || '44922011811';
const ECRV_PIN = process.env.ECRV_PIN || '1234';

function toPositiveInt(value, fallback) {
  const parsed = Number(value);
  if (!Number.isFinite(parsed) || parsed <= 0) return fallback;
  return Math.floor(parsed);
}

function toBool(value, fallback = false) {
  if (value == null) return fallback;
  const v = String(value).trim().toLowerCase();
  if (v === '1' || v === 'true' || v === 'yes' || v === 'y') return true;
  if (v === '0' || v === 'false' || v === 'no' || v === 'n') return false;
  return fallback;
}

// Intervalos (em ms)
const SESSION_REFRESH_INTERVAL = toPositiveInt(
  process.env.TOKEN_UPDATER_SESSION_REFRESH_INTERVAL_MS || process.env.TOKEN_REFRESH_INTERVAL_MS,
  5 * 60 * 1000
); // recarrega pagina
const SESSION_CHECK_INTERVAL = toPositiveInt(
  process.env.TOKEN_UPDATER_SESSION_CHECK_INTERVAL_MS || process.env.TOKEN_REFRESH_CHECK_INTERVAL_MS,
  30 * 1000
); // verifica sessao
const MAX_SESSION_AGE = toPositiveInt(
  process.env.TOKEN_UPDATER_MAX_SESSION_AGE_MS || process.env.TOKEN_REFRESH_MAX_SESSION_AGE_MS,
  55 * 60 * 1000
); // forca login novo
const CDP_URL = String(
  process.env.TOKEN_UPDATER_CDP_URL || process.env.ECRV_CDP_URL || 'http://127.0.0.1:9222'
).trim();
const CDP_CONNECT_TIMEOUT_MS = toPositiveInt(process.env.TOKEN_UPDATER_CDP_CONNECT_TIMEOUT_MS, 5000);
const CDP_CONNECT_RETRIES = toPositiveInt(process.env.TOKEN_UPDATER_CDP_CONNECT_RETRIES, 10);
const CDP_CONNECT_RETRY_DELAY_MS = toPositiveInt(
  process.env.TOKEN_UPDATER_CDP_CONNECT_RETRY_DELAY_MS,
  5000
);
const REQUIRE_ECRV_TAB = toBool(process.env.TOKEN_UPDATER_REQUIRE_ECRV_TAB, true);
const FOCUS_ECRV_TAB = toBool(process.env.TOKEN_UPDATER_FOCUS_ECRV_TAB, true);
const USE_EXISTING_CHROME_ONLY = toBool(process.env.TOKEN_UPDATER_USE_EXISTING_CHROME_ONLY, false);
const AUTO_START_CHROME = toBool(
  process.env.TOKEN_UPDATER_AUTO_START_CHROME,
  !USE_EXISTING_CHROME_ONLY
);
const AUTO_LOGIN_WHEN_NEEDED = toBool(
  process.env.TOKEN_UPDATER_AUTO_LOGIN_WHEN_NEEDED,
  !USE_EXISTING_CHROME_ONLY
);
const KILL_EXISTING_CHROME_ON_START = toBool(
  process.env.TOKEN_UPDATER_KILL_EXISTING_CHROME_ON_START,
  false
);

function parseCdpUrl(value) {
  if (!value) return null;
  const raw = String(value).trim();
  if (!raw) return null;
  try {
    if (raw.startsWith('ws://') || raw.startsWith('wss://') || raw.startsWith('http://') || raw.startsWith('https://')) {
      return new URL(raw);
    }
    return new URL(`http://${raw}`);
  } catch {
    return null;
  }
}

function isWsUrl(value) {
  return /^wss?:\/\//i.test(String(value || '').trim());
}

function inferCdpPort(value) {
  const parsed = parseCdpUrl(value);
  if (!parsed) return 9222;
  if (parsed.port) {
    const parsedPort = Number(parsed.port);
    if (Number.isInteger(parsedPort) && parsedPort > 0) return parsedPort;
  }
  if (parsed.protocol === 'https:' || parsed.protocol === 'wss:') return 443;
  return 9222;
}

function buildCdpCandidates(primaryUrl) {
  const normalizedPrimary = String(primaryUrl || '').trim();
  const candidates = [];
  const append = (url) => {
    const value = String(url || '').trim();
    if (!value) return;
    if (!candidates.includes(value)) candidates.push(value);
  };

  append(normalizedPrimary);

  const parsed = parseCdpUrl(normalizedPrimary);
  if (!parsed) {
    return candidates;
  }

  const host = parsed.hostname.toLowerCase();
  const hostAlternatives = [];
  if (host === '127.0.0.1') {
    hostAlternatives.push('localhost');
  } else if (host === 'localhost') {
    hostAlternatives.push('127.0.0.1');
  }

  for (const altHost of hostAlternatives) {
    const copy = new URL(parsed.toString());
    copy.hostname = altHost;
    append(copy.toString().replace(/\/$/, ''));
  }

  return candidates;
}

function formatConnectionError(error) {
  if (!error) return 'erro desconhecido';
  const parts = [];
  const message = String(error.message || error).trim();
  if (message) parts.push(message);

  const cause = error.cause && typeof error.cause === 'object' ? error.cause : null;
  const code = cause?.code || error.code;
  if (code) parts.push(`code=${code}`);
  if (cause?.errno) parts.push(`errno=${cause.errno}`);
  if (cause?.address) parts.push(`address=${cause.address}`);
  if (cause?.port) parts.push(`port=${cause.port}`);

  return parts.join(' | ');
}

async function probeCdpEndpoint(baseUrl, timeoutMs = 3000) {
  const parsed = parseCdpUrl(baseUrl);
  if (!parsed) {
    return { ok: false, detail: `URL de CDP invalida: ${baseUrl}` };
  }

  if (parsed.protocol.startsWith('ws')) {
    return { ok: true, detail: null };
  }

  let endpoint;
  try {
    endpoint = new URL('/json/version', parsed.toString()).toString();
  } catch {
    return { ok: false, detail: `Nao foi possivel montar /json/version para ${baseUrl}` };
  }

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), Math.max(1000, timeoutMs));
  try {
    const response = await fetch(endpoint, { signal: controller.signal });
    if (!response.ok) {
      return {
        ok: false,
        detail: `Endpoint ${endpoint} respondeu HTTP ${response.status}`,
      };
    }
    const data = await response.json().catch(() => ({}));
    if (!data || typeof data !== 'object' || !data.webSocketDebuggerUrl) {
      return {
        ok: false,
        detail: `Endpoint ${endpoint} respondeu sem webSocketDebuggerUrl`,
      };
    }
    return { ok: true, detail: null };
  } catch (error) {
    return { ok: false, detail: `Falha ao acessar ${endpoint}: ${formatConnectionError(error)}` };
  } finally {
    clearTimeout(timer);
  }
}

const CDP_PORT = inferCdpPort(CDP_URL);
const CDP_CANDIDATE_URLS = buildCdpCandidates(CDP_URL);

let browser = null;
let page = null;
let lastLoginTime = 0;
let lastRefreshTime = 0;
let isRunning = true;

// Logger simples
function log(level, message, ...args) {
  const timestamp = new Date().toISOString();
  const prefix = `[${timestamp}] [${level}] [TokenUpdater]`;
  console.log(prefix, message, ...args);
}

function logInfo(message, ...args) {
  log('INFO', message, ...args);
}

function logWarn(message, ...args) {
  log('WARN', message, ...args);
}

function logError(message, ...args) {
  log('ERROR', message, ...args);
}

/**
 * Encontra a pasta click-automation
 */
function findClickAutomationDir() {
  // Possíveis caminhos relativos ao point-recorder
  const possiblePaths = [
    path.join(__dirname, '../../click-automation'),
    path.join(__dirname, '../../../click-automation'),
    path.join('C:', '/Users/llgru_rj1md3b/Desktop/teste/click-automation'),
    path.join(process.env.USERPROFILE || '', 'Desktop/teste/click-automation'),
    path.join('C:', '/Users/llgru_rj1md3b/Desktop/teste/appll/click-automation'),
    path.join(process.env.USERPROFILE || '', 'Desktop/teste/appll/click-automation'),
  ];

  for (const p of possiblePaths) {
    if (fs.existsSync(p) && fs.existsSync(path.join(p, 'package.json'))) {
      logInfo(`Click-automation encontrado em: ${p}`);
      return p;
    }
  }

  logWarn('Click-automation não encontrado automaticamente');
  return null;
}

/**
 * Salva o JSESSIONID no banco
 */
async function saveJSessionId(jsessionid) {
  if (!jsessionid) {
    return false;
  }

  let connection;
  try {
    connection = await mysql.createConnection(dbConfig);

    const [rows] = await connection.execute(
      'SELECT * FROM admin_settings WHERE `key` = ?',
      ['jsessionid']
    );

    if (rows.length > 0) {
      await connection.execute(
        'UPDATE admin_settings SET `value` = ? WHERE `key` = ?',
        [jsessionid, 'jsessionid']
      );
      logInfo('JSESSIONID atualizado no banco');
    } else {
      await connection.execute(
        'INSERT INTO admin_settings (`key`, `value`) VALUES (?, ?)',
        ['jsessionid', jsessionid]
      );
      logInfo('JSESSIONID inserido no banco');
    }

    return true;
  } catch (error) {
    logError('Erro ao salvar JSESSIONID:', error.message);
    return false;
  } finally {
    if (connection) {
      await connection.end();
    }
  }
}

/**
 * Extrai JSESSIONID dos cookies
 */
async function extractJSessionId() {
  if (!page || page.isClosed()) {
    return null;
  }

  try {
    const cookies = await page.cookies();
    const jsessionidCookie = cookies.find(
      c => c.name === 'JSESSIONID' || c.name === 'jsessionid'
    );

    if (jsessionidCookie && jsessionidCookie.value) {
      return jsessionidCookie.value;
    }

    const documentCookie = await page.evaluate(() => document.cookie);
    const match = documentCookie.match(/JSESSIONID=([^;]+)/i);
    if (match) {
      return match[1];
    }

    return null;
  } catch (error) {
    logError('Erro ao extrair JSESSIONID:', error.message);
    return null;
  }
}

/**
 * Verifica se a sessão está ativa
 */
async function isSessionActive() {
  if (!page || page.isClosed()) {
    return false;
  }

  try {
    const url = page.url();
    if (!url.includes('e-crvsp.sp.gov.br')) {
      return false;
    }

    const jsessionid = await extractJSessionId();
    return !!jsessionid;
  } catch {
    return false;
  }
}

/**
 * Executa a automação de login usando o Node.js (run-ecrv.js)
 */
async function runLoginAutomation() {
  logInfo('Iniciando automação de login no E-CRV...');

  const clickAutomationDir = findClickAutomationDir();
  if (!clickAutomationDir) {
    logError('Click-automation não encontrado!');
    return false;
  }

  const scriptPath = path.join(clickAutomationDir, 'run-ecrv.js');

  if (!fs.existsSync(scriptPath)) {
    logError(`Script Node.js não encontrado: ${scriptPath}`);
    return false;
  }

  logInfo(`Executando: ${scriptPath}`);
  logInfo('Usando Chrome já aberto (modo --no-chrome)');

  return new Promise((resolve) => {
    const nodeProc = spawn('node', [
      scriptPath,
      '--cpf',
      ECRV_CPF,
      '--pin',
      ECRV_PIN,
      '--wait',
      '0',
      '--no-chrome',
    ], {
      stdio: 'inherit',
      cwd: clickAutomationDir,
    });

    nodeProc.on('exit', (code) => {
      if (code === 0) {
        logInfo('Automação de login concluída com sucesso');
        lastLoginTime = Date.now();
        resolve(true);
      } else {
        logWarn(`Automação de login finalizada com código ${code}`);
        resolve(false);
      }
    });

    nodeProc.on('error', (error) => {
      logError('Erro ao executar automação:', error.message);
      resolve(false);
    });
  });
}

/**
 * Conecta ao Chrome em execução
 */
async function connectToChrome() {
  const maxRetries = CDP_CONNECT_RETRIES;
  let retryCount = 0;

  while (retryCount < maxRetries && isRunning) {
    const attemptNumber = retryCount + 1;
    try {
      logInfo(
        `Tentando conectar ao Chrome (CDP ${CDP_URL})... tentativa ${attemptNumber}/${maxRetries}`
      );

      const failureDetails = [];

      for (const candidateUrl of CDP_CANDIDATE_URLS) {
        const cdpProbe = await probeCdpEndpoint(candidateUrl, CDP_CONNECT_TIMEOUT_MS);
        if (!cdpProbe.ok) {
          failureDetails.push(`${candidateUrl} -> ${cdpProbe.detail}`);
          continue;
        }

        try {
          browser = await puppeteer.connect(
            isWsUrl(candidateUrl)
              ? {
                  browserWSEndpoint: candidateUrl,
                  defaultViewport: null,
                  timeout: CDP_CONNECT_TIMEOUT_MS,
                }
              : {
                  browserURL: candidateUrl,
                  defaultViewport: null,
                  timeout: CDP_CONNECT_TIMEOUT_MS,
                }
          );

          const pages = await browser.pages();
          let targetPage = pages.find(p => p.url().includes('e-crvsp.sp.gov.br'));

          if (!targetPage && !REQUIRE_ECRV_TAB && pages.length > 0) {
            targetPage = pages[0];
          }

          if (targetPage) {
            page = targetPage;
            if (FOCUS_ECRV_TAB) {
              try {
                await page.bringToFront();
              } catch {
                // ignore
              }
            }
            if (candidateUrl !== CDP_URL) {
              logInfo(`Conectado ao Chrome via endpoint alternativo: ${candidateUrl}`);
            } else {
              logInfo('Conectado ao Chrome!');
            }
            return true;
          }

          logWarn(`Conectou em ${candidateUrl}, mas nao encontrei aba do E-CRV no Chrome conectado.`);
          await browser.disconnect();
          browser = null;
        } catch (candidateError) {
          failureDetails.push(
            `${candidateUrl} -> ${formatConnectionError(candidateError)}`
          );
          if (browser) {
            try {
              await browser.disconnect();
            } catch {}
            browser = null;
          }
        }
      }

      retryCount++;
      if (failureDetails.length > 0) {
        logWarn(`Tentativa ${retryCount}/${maxRetries} falhou: ${failureDetails.join(' || ')}`);
      } else {
        logWarn(`Tentativa ${retryCount}/${maxRetries} falhou: sem detalhes adicionais`);
      }

      if (USE_EXISTING_CHROME_ONLY) {
        logInfo(
          `Modo "usar Chrome existente" ativo. Verifique se o Chrome foi iniciado com ` +
          `"--remote-debugging-port=${CDP_PORT}" e se ${CDP_URL}/json/version responde.`
        );
      }

      if (retryCount < maxRetries) {
        logInfo(`Aguardando ${CDP_CONNECT_RETRY_DELAY_MS}ms antes de tentar novamente...`);
        await sleep(CDP_CONNECT_RETRY_DELAY_MS);
      }
    } catch (error) {
      retryCount++;
      logWarn(`Tentativa ${retryCount}/${maxRetries} falhou: ${formatConnectionError(error)}`);
      if (retryCount < maxRetries) {
        logInfo(`Aguardando ${CDP_CONNECT_RETRY_DELAY_MS}ms antes de tentar novamente...`);
        await sleep(CDP_CONNECT_RETRY_DELAY_MS);
      }
    }
  }

  return false;
}

/**
 * Inicia o Chrome com remote debugging
 */
async function startChrome() {
  logInfo('Iniciando Chrome com remote debugging...');

  if (KILL_EXISTING_CHROME_ON_START) {
    // Opcional: matar Chrome existente (desativado por padrao).
    try {
      spawn('taskkill', ['/F', '/IM', 'chrome.exe'], { stdio: 'ignore' });
      await new Promise(r => setTimeout(r, 2000));
    } catch {}
  }

  const chromePaths = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    path.join(process.env.LOCALAPPDATA || '', 'Google\\Chrome\\Application\\chrome.exe'),
  ];

  let chromePath = chromePaths.find(p => fs.existsSync(p));

  if (!chromePath) {
    chromePath = 'chrome.exe';
  }

  const userDataDir = path.join(process.env.TEMP || '', 'chrome-ecrv-token-updater');
  const args = [
    `--remote-debugging-port=${CDP_PORT}`,
    `--user-data-dir=${userDataDir}`,
    '--no-first-run',
    '--no-default-browser-check',
    '--start-maximized',
    ECRV_URL,
  ];

  logInfo(`Chrome: ${chromePath}`);
  logInfo(`Args: ${args.join(' ')}`);

  const chrome = spawn(chromePath, args, {
    detached: true,
    stdio: 'ignore',
    shell: false,
  });

  chrome.unref();
  logInfo(`Chrome iniciado (PID: ${chrome.pid})`);

  // Aguardar Chrome iniciar
  logInfo('Aguardando Chrome iniciar (15 segundos)...');
  await new Promise(r => setTimeout(r, 15000));
}

/**
 * Loop principal
 */
async function sessionMaintenanceLoop() {
  logInfo('Iniciando loop de manutenção de sessão...');

  while (isRunning) {
    try {
      await new Promise(r => setTimeout(r, SESSION_CHECK_INTERVAL));

      if (!isRunning) {
        continue;
      }

      if (!browser || !page || page.isClosed()) {
        await connectToChrome();
        continue;
      }

      const now = Date.now();
      const baseLoginTime = lastLoginTime > 0 ? lastLoginTime : now;
      const sessionAge = now - baseLoginTime;

      logInfo(`Verificando sessão... (idade: ${Math.floor(sessionAge / 1000)}s)`);

      if (sessionAge > MAX_SESSION_AGE) {
        if (AUTO_LOGIN_WHEN_NEEDED) {
          logInfo('Sessão muito antiga, fazendo login novo...');
          await runLoginAutomation();
          await connectToChrome();
        } else {
          logWarn('Sessao antiga detectada, mas auto-login esta desativado. Mantendo sessao atual.');
          lastLoginTime = now;
        }
        continue;
      }

      const active = await isSessionActive();
      if (!active) {
        if (AUTO_LOGIN_WHEN_NEEDED) {
          logWarn('Sessão não está ativa, tentando recuperar com auto-login...');
          await runLoginAutomation();
          await connectToChrome();
        } else {
          logWarn('Sessao nao esta ativa e auto-login desativado. Tentando apenas refresh da aba atual...');
          try {
            await page.reload({ waitUntil: 'domcontentloaded' });
            await new Promise(r => setTimeout(r, 2000));
          } catch (reloadErr) {
            logWarn('Falha ao recarregar aba atual:', reloadErr.message);
          }
        }
        continue;
      }

      if (lastLoginTime <= 0) {
        lastLoginTime = now;
      }

      const jsessionid = await extractJSessionId();
      if (jsessionid) {
        await saveJSessionId(jsessionid);
        logInfo('JSESSIONID salvo:', jsessionid.substring(0, 10) + '...');
      }

      if (lastRefreshTime <= 0) {
        lastRefreshTime = now;
      }

      if ((now - lastRefreshTime) >= SESSION_REFRESH_INTERVAL) {
        logInfo('Recarregando página para manter sessão ativa...');
        try {
          await page.reload({ waitUntil: 'domcontentloaded' });
          await new Promise(r => setTimeout(r, 3000));
          lastRefreshTime = Date.now();
        } catch (error) {
          logWarn('Erro ao recarregar página:', error.message);
        }
      }

    } catch (error) {
      logError('Erro no loop de manutenção:', error.message);
    }
  }
}

async function main() {
  logInfo('='.repeat(60));
  logInfo('TOKEN UPDATER - E-CRV SP');
  logInfo('='.repeat(60));
  logInfo(`URL: ${ECRV_URL}`);
  logInfo(`CPF: ${ECRV_CPF}`);
  logInfo(`Session refresh interval: ${SESSION_REFRESH_INTERVAL}ms`);
  logInfo(`Session check interval: ${SESSION_CHECK_INTERVAL}ms`);
  logInfo(`Max session age: ${MAX_SESSION_AGE}ms`);
  logInfo(`CDP URL: ${CDP_URL}`);
  logInfo(`CDP candidates: ${CDP_CANDIDATE_URLS.join(', ')}`);
  logInfo(`CDP connect timeout: ${CDP_CONNECT_TIMEOUT_MS}ms`);
  logInfo(`Use existing chrome only: ${USE_EXISTING_CHROME_ONLY}`);
  logInfo(`Auto start chrome: ${AUTO_START_CHROME}`);
  logInfo(`Auto login when needed: ${AUTO_LOGIN_WHEN_NEEDED}`);
  logInfo(`Click-automation dir: ${findClickAutomationDir() || 'NÃO ENCONTRADO'}`);
  logInfo('='.repeat(60));

  process.on('SIGINT', () => shutdown(0));
  process.on('SIGTERM', () => shutdown(0));

  try {
    if (AUTO_START_CHROME) {
      await startChrome();
    }

    if (AUTO_LOGIN_WHEN_NEEDED) {
      await runLoginAutomation();
    }

    if (!await connectToChrome()) {
      throw new Error(
        `Nao foi possivel conectar ao Chrome via CDP (${CDP_URL}). ` +
        `Garanta que o Chrome do E-CRV esteja aberto com --remote-debugging-port=${CDP_PORT}.`
      );
    }

    const initialSessionToken = await extractJSessionId();
    if (initialSessionToken) {
      await saveJSessionId(initialSessionToken);
      lastLoginTime = Date.now();
      lastRefreshTime = Date.now();
      logInfo('Token inicial capturado da janela atual.');
    } else {
      logWarn('Nao foi encontrado JSESSIONID inicial na aba atual. O updater vai continuar monitorando.');
      lastRefreshTime = Date.now();
    }

    await sessionMaintenanceLoop();

  } catch (error) {
    logError('Erro fatal:', error.message);
    shutdown(1);
  }
}

async function shutdown(exitCode = 0) {
  if (!isRunning) return;

  logInfo('Encerrando token updater...');
  isRunning = false;

  if (browser) {
    try {
      await browser.disconnect();
    } catch {}
  }

  logInfo('Token updater encerrado.');
  process.exit(exitCode);
}

main().catch((error) => {
  logError('Erro não tratado:', error);
  process.exit(1);
});
