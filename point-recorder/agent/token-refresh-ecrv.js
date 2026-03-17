/**
 * Token Refresh para E-CRV SP - Versão Simplificada
 *
 * NÃO recarrega a página. Apenas:
 * 1. Conecta ao Chrome existente
 * 2. Extrai JSESSIONID dos cookies
 * 3. Salva no banco de dados periodicamente
 * 4. Só faz login novo se a sessão for perdida
 *
 * Uso:
 *   node agent/token-refresh-ecrv.js [--interval <segundos>]
 */

import mysql from 'mysql2/promise';
import puppeteer from 'puppeteer';

// Configuração do banco de dados
const dbConfig = {
  host: '193.203.175.152',
  port: 3306,
  database: 'u365250089_appll',
  user: 'u365250089_appll',
  password: 'Skala@2025$',
};

// Configurações padrão
const DEFAULT_CONFIG = {
  checkInterval: 30, // segundos - intervalo para verificar/atualizar token
  maxRetryTime: 5 * 60 * 1000, // 5 minutos - tempo máximo para tentar reconectar
};

let isRunning = true;
let browser = null;
let page = null;

function parseArgs(argv) {
  const args = { ...DEFAULT_CONFIG };

  for (let i = 0; i < argv.length; i += 1) {
    const a = argv[i];
    if (a === '--help' || a === '-h') {
      args.help = true;
      continue;
    }
    if ((a === '--interval') && argv[i + 1]) {
      args.checkInterval = Number(argv[i + 1]);
      i += 1;
      continue;
    }
  }

  return args;
}

function usage() {
  console.log('Uso: node agent/token-refresh-ecrv.js [opcoes]');
  console.log('');
  console.log('Opcoes:');
  console.log('  --interval <segundos>  Intervalo de verificacao (default: 30s)');
  console.log('  --help, -h             Mostra esta ajuda');
  console.log('');
  console.log('Este script:');
  console.log('  - NAO recarrega a pagina do E-CRV');
  console.log('  - Apenas extrai e salva o JSESSIONID periodicamente');
  console.log('  - Requer Chrome rodando com --remote-debugging-port=9222');
}

function log(level, message) {
  const timestamp = new Date().toISOString();
  console.log(`[${timestamp}] [${level}] [TOKEN-REFRESH] ${message}`);
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
      // Verificar se o valor mudou antes de atualizar
      if (rows[0].value !== jsessionid) {
        await connection.execute(
          'UPDATE admin_settings SET `value` = ? WHERE `key` = ?',
          [jsessionid, 'jsessionid']
        );
        log('INFO', 'JSESSIONID atualizado no banco');
      }
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

    // Tentar pegar do document.cookie também
    const documentCookie = await page.evaluate(() => {
      return document.cookie;
    }).catch(() => '');

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
 * Verifica se está na pagina do E-CRV
 */
async function isInEcrvPage() {
  if (!page || page.isClosed()) {
    return false;
  }

  try {
    const url = page.url();
    return url.includes('e-crvsp.sp.gov.br');
  } catch {
    return false;
  }
}

/**
 * Conecta ao Chrome em execução
 */
async function connectToChrome(maxRetries = 20) {
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

      // Procurar aba do E-CRV
      let targetPage = pages.find(p => p.url().includes('e-crvsp.sp.gov.br'));

      if (!targetPage && pages.length > 0) {
        // Se não achou, usar a primeira aba
        targetPage = pages[0];
        log('WARN', 'Nao encontrada aba do E-CRV, usando primeira aba');
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

  log('ERROR', 'Nao foi possivel conectar ao Chrome');
  return false;
}

/**
 * Loop principal - apenas extrai e salva token periodicamente
 */
async function tokenRefreshLoop(config) {
  const checkIntervalMs = config.checkInterval * 1000;
  let lastJsessionId = null;
  let consecutiveErrors = 0;

  log('INFO', `Iniciando loop de refresh de token...`);
  log('INFO', `  - Intervalo: ${config.checkInterval} segundos`);
  log('INFO', `  - NAO recarrega a pagina (apenas extrai cookie)`);

  while (isRunning) {
    try {
      await new Promise(r => setTimeout(r, checkIntervalMs));

      if (!isRunning) break;

      // Verificar se ainda conectado
      if (!browser || !page || page.isClosed()) {
        log('WARN', 'Conexao com Chrome perdida, tentando reconectar...');
        if (await connectToChrome()) {
          consecutiveErrors = 0;
        } else {
          consecutiveErrors++;
          if (consecutiveErrors > 10) {
            log('ERROR', 'Muitas falhas consecutivas. Encerrando.');
            break;
          }
          continue;
        }
      }

      // Verificar se está na pagina do E-CRV
      const inEcrv = await isInEcrvPage();
      if (!inEcrv) {
        log('WARN', 'Nao estah na pagina do E-CRV. Esperando...');
        continue;
      }

      // Extrair JSESSIONID
      const jsessionid = await extractJSessionId();

      if (jsessionid) {
        // Só salva se for diferente do ultimo
        if (jsessionid !== lastJsessionId) {
          log('INFO', `JSESSIONID: ${jsessionid.substring(0, 10)}... (alterado)`);
          await saveJSessionId(jsessionid);
          lastJsessionId = jsessionid;
        } else {
          log('INFO', `JSESSIONID: ${jsessionid.substring(0, 10)}... (igual)`);
        }
        consecutiveErrors = 0;
      } else {
        log('WARN', 'Nao foi possivel extrair JSESSIONID');
        consecutiveErrors++;
      }

    } catch (error) {
      log('ERROR', `Erro no loop: ${error.message}`);
      consecutiveErrors++;

      if (consecutiveErrors > 20) {
        log('ERROR', 'Muitas falhas consecutivas. Encerrando.');
        break;
      }
    }
  }
}

function shutdown() {
  if (!isRunning) return;

  log('INFO', 'Encerrando token refresh...');
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
  console.log('TOKEN REFRESH - E-CRV SP (Versao Simplificada)');
  console.log('='.repeat(60));
  console.log(`\nIntervalo de verificacao: ${args.checkInterval} segundos`);
  console.log('\nEste script:');
  console.log('  - NAO faz login');
  console.log('  - NAO recarrega a pagina');
  console.log('  - Apenas extrai e salva o JSESSIONID periodicamente');
  console.log('\nPressione Ctrl+C para encerrar\n');
  console.log('='.repeat(60) + '\n');

  process.on('SIGINT', shutdown);
  process.on('SIGTERM', shutdown);

  try {
    // 1. Conectar ao Chrome
    if (!await connectToChrome()) {
      throw new Error('Nao foi possivel conectar ao Chrome.');
    }

    // 2. Extrair e salvar token inicial
    const initialToken = await extractJSessionId();
    if (initialToken) {
      await saveJSessionId(initialToken);
      log('INFO', `Token inicial: ${initialToken.substring(0, 10)}...`);
    } else {
      log('WARN', 'Nao foi possivel extrair token inicial. Verifique se estah logado no E-CRV.');
    }

    // 3. Iniciar loop de refresh
    await tokenRefreshLoop(args);

  } catch (error) {
    log('ERROR', `Erro fatal: ${error.message}`);
    shutdown();
  }
}

main().catch((err) => {
  log('ERROR', `Erro nao tratado: ${err}`);
  process.exit(1);
});
