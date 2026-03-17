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

// Intervalos (em ms)
const SESSION_REFRESH_INTERVAL = 5 * 60 * 1000; // 5 minutos - recarrega página
const SESSION_CHECK_INTERVAL = 30 * 1000; // 30 segundos - verifica sessão
const MAX_SESSION_AGE = 55 * 60 * 1000; // 55 minutos - força login novo

let browser = null;
let page = null;
let lastLoginTime = 0;
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
  const maxRetries = 10;
  let retryCount = 0;

  while (retryCount < maxRetries && isRunning) {
    try {
      logInfo(`Tentando conectar ao Chrome (porta 9222)... tentativa ${retryCount + 1}/${maxRetries}`);

      browser = await puppeteer.connect({
        browserURL: 'http://127.0.0.1:9222',
        defaultViewport: null,
        timeout: 5000,
      });

      const pages = await browser.pages();
      let targetPage = pages.find(p => p.url().includes('e-crvsp.sp.gov.br'));

      if (!targetPage && pages.length > 0) {
        targetPage = pages[0];
      }

      if (targetPage) {
        page = targetPage;
        logInfo('Conectado ao Chrome!');
        return true;
      }

      await browser.disconnect();
      browser = null;

    } catch (error) {
      retryCount++;
      logWarn(`Tentativa ${retryCount}/${maxRetries} falhou: ${error.message}`);

      if (retryCount < maxRetries) {
        logInfo('Aguardando 5 segundos antes de tentar novamente...');
        await new Promise(r => setTimeout(r, 5000));
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

  // Matar Chrome existente
  try {
    spawn('taskkill', ['/F', '/IM', 'chrome.exe'], { stdio: 'ignore' });
    await new Promise(r => setTimeout(r, 2000));
  } catch {}

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
    '--remote-debugging-port=9222',
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

      if (!isRunning || !browser || !page) {
        continue;
      }

      const now = Date.now();
      const sessionAge = now - lastLoginTime;

      logInfo(`Verificando sessão... (idade: ${Math.floor(sessionAge / 1000)}s)`);

      if (sessionAge > MAX_SESSION_AGE) {
        logInfo('Sessão muito antiga, fazendo login novo...');
        await runLoginAutomation();
        await connectToChrome();
        continue;
      }

      const active = await isSessionActive();
      if (!active) {
        logWarn('Sessão não está ativa, tentando recuperar...');
        await runLoginAutomation();
        await connectToChrome();
        continue;
      }

      const jsessionid = await extractJSessionId();
      if (jsessionid) {
        await saveJSessionId(jsessionid);
        logInfo('JSESSIONID salvo:', jsessionid.substring(0, 10) + '...');
      }

      if (sessionAge > 0 && sessionAge % SESSION_REFRESH_INTERVAL < SESSION_CHECK_INTERVAL) {
        logInfo('Recarregando página para manter sessão ativa...');
        try {
          await page.reload({ waitUntil: 'domcontentloaded' });
          await new Promise(r => setTimeout(r, 3000));
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
  logInfo(`Click-automation dir: ${findClickAutomationDir() || 'NÃO ENCONTRADO'}`);
  logInfo('='.repeat(60));

  process.on('SIGINT', () => shutdown(0));
  process.on('SIGTERM', () => shutdown(0));

  try {
    await startChrome();
    await runLoginAutomation();

    if (!await connectToChrome()) {
      throw new Error('Não foi possível conectar ao Chrome');
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
