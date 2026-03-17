/**
 * Script para executar a automação do E-CRV SP + Token Refresh
 *
 * Faz login inicial no E-CRV e depois mantém o token ativo com refresh periódico
 *
 * Uso:
 *   node run-ecrv-with-refresh.js [--cpf <cpf>] [--pin <pin>] [--interval <minutos>]
 */

import { spawn } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';
import mysql from 'mysql2/promise';
import puppeteer from 'puppeteer';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// Configuração do banco de dados
const dbConfig = {
  host: '193.203.175.152',
  port: 3306,
  database: 'u365250089_appll',
  user: 'u365250089_appll',
  password: 'Skala@2025$',
};

const ECRV_URL = 'https://www.e-crvsp.sp.gov.br/';

// Configurações padrão
const DEFAULT_CONFIG = {
  cpf: '44922011811',
  pin: '1234',
  refreshInterval: 5, // minutos
  maxSessionAge: 55, // minutos - força login novo
};

let isRunning = true;
let browser = null;
let page = null;
let lastLoginTime = 0;

function parseArgs(argv) {
  const args = { ...DEFAULT_CONFIG };

  for (let i = 0; i < argv.length; i += 1) {
    const a = argv[i];
    if (a === '--help' || a === '-h') {
      args.help = true;
      continue;
    }
    if ((a === '--cpf') && argv[i + 1]) {
      args.cpf = argv[i + 1];
      i += 1;
      continue;
    }
    if ((a === '--pin') && argv[i + 1]) {
      args.pin = argv[i + 1];
      i += 1;
      continue;
    }
    if ((a === '--interval') && argv[i + 1]) {
      args.refreshInterval = Number(argv[i + 1]);
      i += 1;
      continue;
    }
  }

  return args;
}

function usage() {
  console.log('Uso: node run-ecrv-with-refresh.js [opcoes]');
  console.log('');
  console.log('Opcoes:');
  console.log('  --cpf <cpf>          CPF para login (default: 44922011811)');
  console.log('  --pin <pin>          PIN para login (default: 1234)');
  console.log('  --interval <minutos> Intervalo de refresh (default: 5 minutos)');
  console.log('  --help, -h           Mostra esta ajuda');
  console.log('');
  console.log('Exemplos:');
  console.log('  node run-ecrv-with-refresh.js');
  console.log('  node run-ecrv-with-refresh.js --cpf 12345678901 --pin 4321 --interval 10');
}

function log(level, message) {
  const timestamp = new Date().toISOString();
  console.log(`[${timestamp}] [${level}] ${message}`);
}

/**
 * Executa a automação de login via PowerShell
 */
async function runLoginAutomation(cpf, pin) {
  log('INFO', 'Iniciando automação de login no E-CRV...');

  const psScript = path.join(__dirname, 'run-ecrv-flow.ps1');

  return new Promise((resolve) => {
    const psArgs = [
      '-NoProfile',
      '-ExecutionPolicy',
      'Bypass',
      '-File',
      psScript,
      '-FlowFile',
      'e-crv-flow.json',
      '-Cpf',
      cpf,
      '-Pin',
      pin,
      '-PreWaitMs',
      '0',
    ];

    const ps = spawn('powershell.exe', psArgs, {
      stdio: 'inherit',
      cwd: __dirname,
    });

    ps.on('exit', (code) => {
      if (code === 0) {
        log('INFO', 'Automação de login concluída com sucesso');
        lastLoginTime = Date.now();
        resolve(true);
      } else {
        log('WARN', `Automação de login finalizada com código ${code}`);
        resolve(false);
      }
    });

    ps.on('error', (error) => {
      log('ERROR', `Erro ao executar automação: ${error.message}`);
      resolve(false);
    });
  });
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
      log('INFO', 'JSESSIONID atualizado no banco');
    } else {
      await connection.execute(
        'INSERT INTO admin_settings (`key`, `value`) VALUES (?, ?)',
        ['jsessionid', jsessionid]
      );
      log('INFO', 'JSESSIONID inserido no banco');
    }

    return true;
  } catch (error) {
    log('ERROR', `Erro ao salvar JSESSIONID: ${error.message}`);
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
    log('ERROR', `Erro ao extrair JSESSIONID: ${error.message}`);
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
 * Conecta ao Chrome em execução
 */
async function connectToChrome(maxRetries = 10) {
  let retryCount = 0;

  while (retryCount < maxRetries && isRunning) {
    try {
      log('INFO', `Tentando conectar ao Chrome (porta 9222)... tentativa ${retryCount + 1}/${maxRetries}`);

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
        log('INFO', 'Conectado ao Chrome!');
        return true;
      }

      await browser.disconnect();
      browser = null;

    } catch (error) {
      retryCount++;
      log('WARN', `Tentativa ${retryCount}/${maxRetries} falhou: ${error.message}`);

      if (retryCount < maxRetries) {
        log('INFO', 'Aguardando 5 segundos antes de tentar novamente...');
        await new Promise(r => setTimeout(r, 5000));
      }
    }
  }

  return false;
}

/**
 * Loop principal de manutenção de sessão
 */
async function sessionMaintenanceLoop(config) {
  const checkInterval = 30 * 1000; // 30 segundos
  const refreshInterval = config.refreshInterval * 60 * 1000;
  const maxSessionAge = config.maxSessionAge * 60 * 1000;

  log('INFO', `Iniciando loop de manutenção de sessão...`);
  log('INFO', `  - Intervalo de refresh: ${config.refreshInterval} minutos`);
  log('INFO', `  - Idade máxima da sessão: ${config.maxSessionAge} minutos`);

  while (isRunning) {
    try {
      await new Promise(r => setTimeout(r, checkInterval));

      if (!isRunning || !browser || !page) {
        continue;
      }

      const now = Date.now();
      const sessionAge = now - lastLoginTime;

      log('INFO', `Verificando sessão... (idade: ${Math.floor(sessionAge / 1000)}s)`);

      // Sessão muito antiga - fazer login novo
      if (sessionAge > maxSessionAge) {
        log('INFO', 'Sessão muito antiga, fazendo login novo...');
        await runLoginAutomation(config.cpf, config.pin);
        await connectToChrome();
        continue;
      }

      // Verificar se sessão está ativa
      const active = await isSessionActive();
      if (!active) {
        log('WARN', 'Sessão não está ativa, tentando recuperar...');
        await runLoginAutomation(config.cpf, config.pin);
        await connectToChrome();
        continue;
      }

      // Extrair e salvar JSESSIONID
      const jsessionid = await extractJSessionId();
      if (jsessionid) {
        await saveJSessionId(jsessionid);
        log('INFO', `JSESSIONID salvo: ${jsessionid.substring(0, 10)}...`);
      }

      // Recarregar página periodicamente para manter sessão ativa
      if (sessionAge > 0 && sessionAge % refreshInterval < checkInterval) {
        log('INFO', 'Recarregando página para manter sessão ativa...');
        try {
          await page.reload({ waitUntil: 'domcontentloaded' });
          await new Promise(r => setTimeout(r, 3000));
        } catch (error) {
          log('WARN', `Erro ao recarregar página: ${error.message}`);
        }
      }

    } catch (error) {
      log('ERROR', `Erro no loop de manutenção: ${error.message}`);
    }
  }
}

function shutdown() {
  if (!isRunning) return;

  log('INFO', 'Encerrando...');
  isRunning = false;

  if (browser) {
    browser.disconnect().catch(() => {});
  }

  process.exit(0);
}

async function main() {
  const args = parseArgs(process.argv.slice(2));
  if (args.help) {
    usage();
    return;
  }

  console.log('\n' + '='.repeat(60));
  console.log('🤖 E-CRV SP - TOKEN REFRESH AUTOMÁTICO');
  console.log('='.repeat(60));
  console.log(`\n📋 CPF: ${args.cpf}`);
  console.log(`🔑 PIN: ${args.pin}`);
  console.log(`🔄 Intervalo de refresh: ${args.refreshInterval} minutos`);
  console.log(`\n⚠️  Pressione Ctrl+C para encerrar\n`);
  console.log('='.repeat(60) + '\n');

  process.on('SIGINT', shutdown);
  process.on('SIGTERM', shutdown);

  try {
    // 1. Fazer login inicial
    log('INFO', 'Fazendo login inicial no E-CRV...');
    const loginSuccess = await runLoginAutomation(args.cpf, args.pin);

    if (!loginSuccess) {
      log('WARN', 'Login inicial falhou, mas tentando continuar...');
    }

    // 2. Conectar ao Chrome
    if (!await connectToChrome()) {
      throw new Error('Não foi possível conectar ao Chrome');
    }

    // 3. Extrair e salvar token inicial
    const initialToken = await extractJSessionId();
    if (initialToken) {
      await saveJSessionId(initialToken);
      log('INFO', `Token inicial salvo: ${initialToken.substring(0, 10)}...`);
    }

    // 4. Iniciar loop de manutenção
    await sessionMaintenanceLoop(args);

  } catch (error) {
    log('ERROR', `Erro fatal: ${error.message}`);
    shutdown();
  }
}

main().catch((err) => {
  log('ERROR', `Erro não tratado: ${err}`);
  process.exit(1);
});
