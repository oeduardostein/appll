#!/usr/bin/env node

/**
 * Extrai JSESSIONID/PHPSESSID via CDP (Chrome ja aberto) e salva no MySQL.
 */

const CDP_URL = String(process.env.ECRV_CDP_URL || 'http://127.0.0.1:9222').trim();
const CDP_RETRIES = Number(process.env.ECRV_CDP_RETRIES || 12);
const CDP_RETRY_DELAY_MS = Number(process.env.ECRV_CDP_RETRY_DELAY_MS || 2000);

const dbConfig = {
  host: '193.203.175.152',
  port: 3306,
  database: 'u365250089_appll',
  user: 'u365250089_appll',
  password: 'Skala@2025$',
};

console.log('='.repeat(60));
console.log('INICIANDO EXTRACAO DE JSESSIONID...');
console.log('='.repeat(60));

let chromium;
let mysql;

try {
  console.log('[1/5] Importando playwright...');
  const playwrightModule = await import('playwright');
  chromium = playwrightModule.chromium;
  console.log('       OK - Playwright importado');
} catch (error) {
  console.error('       ERRO - Playwright nao encontrado:', error.message);
  process.exit(1);
}

try {
  console.log('[2/5] Importando mysql2...');
  const mysqlModule = await import('mysql2/promise');
  mysql = mysqlModule.default;
  console.log('       OK - mysql2 importado');
} catch (error) {
  console.error('       ERRO - mysql2 nao encontrado:', error.message);
  process.exit(1);
}

function delay(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function normalizeRetries(value, fallback) {
  if (!Number.isFinite(value) || value <= 0) {
    return fallback;
  }
  return Math.trunc(value);
}

function normalizeDelay(value, fallback) {
  if (!Number.isFinite(value) || value < 0) {
    return fallback;
  }
  return Math.trunc(value);
}

async function connectToChromeOverCdp() {
  const retries = normalizeRetries(CDP_RETRIES, 12);
  const retryDelayMs = normalizeDelay(CDP_RETRY_DELAY_MS, 2000);
  let lastError = null;

  console.log('[3/5] Conectando ao Chrome via CDP...');
  console.log(`       URL: ${CDP_URL}`);
  console.log(`       Retries: ${retries} | Delay: ${retryDelayMs}ms`);

  for (let attempt = 1; attempt <= retries; attempt += 1) {
    try {
      if (attempt > 1) {
        console.log(`       Tentativa ${attempt}/${retries}...`);
      }

      const browser = await chromium.connectOverCDP(CDP_URL);
      console.log('       OK - Conectado ao Chrome');
      return browser;
    } catch (error) {
      lastError = error;
      if (attempt < retries) {
        await delay(retryDelayMs);
      }
    }
  }

  const detail = lastError ? lastError.message : 'sem detalhes';
  throw new Error(`Falha ao conectar no CDP (${CDP_URL}) apos ${retries} tentativas: ${detail}`);
}

function findSessionCookie(cookies) {
  if (!Array.isArray(cookies) || cookies.length === 0) {
    return null;
  }

  const byName = (name) =>
    cookies.find(
      (cookie) =>
        String(cookie?.name || '').toUpperCase() === name &&
        typeof cookie?.value === 'string' &&
        cookie.value.length > 0
    );

  const jsession = byName('JSESSIONID');
  if (jsession) {
    return { name: 'JSESSIONID', value: jsession.value };
  }

  const phpSession = byName('PHPSESSID');
  if (phpSession) {
    return { name: 'PHPSESSID', value: phpSession.value };
  }

  return null;
}

async function extractSessionIdFromContexts(browser) {
  console.log('[4/5] Lendo cookies dos browser contexts...');
  const contexts = browser.contexts();
  console.log(`       Contextos encontrados: ${contexts.length}`);

  if (contexts.length === 0) {
    throw new Error('Conectou no CDP, mas nao ha browser contexts disponiveis.');
  }

  for (let i = 0; i < contexts.length; i += 1) {
    const context = contexts[i];
    let cookies = [];
    try {
      cookies = await context.cookies();
    } catch (error) {
      console.log(`       Contexto ${i + 1}: erro ao ler cookies (${error.message})`);
      continue;
    }

    console.log(`       Contexto ${i + 1}: ${cookies.length} cookies`);
    const token = findSessionCookie(cookies);

    if (token) {
      console.log(`       OK - ${token.name} encontrado no contexto ${i + 1}`);
      return token.value;
    }
  }

  throw new Error('Conectou no CDP, mas nao encontrou JSESSIONID/PHPSESSID em nenhum context.');
}

async function saveSessionId(sessionId) {
  console.log('[5/5] Salvando JSESSIONID no banco...');
  console.log(`       Valor: ${sessionId}`);

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
        [sessionId, 'jsessionid']
      );
      console.log('       OK - Registro atualizado');
    } else {
      await connection.execute(
        'INSERT INTO admin_settings (`key`, `value`) VALUES (?, ?)',
        ['jsessionid', sessionId]
      );
      console.log('       OK - Registro inserido');
    }

    return true;
  } finally {
    if (connection) {
      await connection.end();
    }
  }
}

async function main() {
  try {
    const browser = await connectToChromeOverCdp();
    const sessionId = await extractSessionIdFromContexts(browser);
    await saveSessionId(sessionId);

    console.log('');
    console.log('='.repeat(60));
    console.log('SUCESSO! JSESSIONID salvo no banco.');
    console.log('='.repeat(60));
    process.exit(0);
  } catch (error) {
    console.log('');
    console.log('='.repeat(60));
    console.log('FALHA! Nao foi possivel capturar/salvar o JSESSIONID.');
    console.log(`ERRO: ${error.message}`);
    console.log('='.repeat(60));
    process.exit(1);
  }
}

main();
