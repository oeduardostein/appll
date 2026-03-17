#!/usr/bin/env node

/**
 * Script para capturar o JSESSIONID usando CDP (Chrome DevTools Protocol)
 * Versão alternativa que não depende do Puppeteer
 */

import mysql from 'mysql2/promise';

// Configuração do banco de dados
const dbConfig = {
  host: '193.203.175.152',
  port: 3306,
  database: 'u365250089_appll',
  user: 'u365250089_appll',
  password: 'Skala@2025$',
};

/**
 * Salva o jsessionid no banco de dados
 */
async function saveJSessionId(jsessionid) {
  if (!jsessionid) {
    console.error('❌ Erro: jsessionid não fornecido');
    return false;
  }

  console.log('\n📋 JSESSIONID capturado:', jsessionid);

  let connection;
  try {
    console.log('🔌 Conectando ao banco de dados...');
    connection = await mysql.createConnection(dbConfig);
    console.log('✅ Conectado ao banco!');

    console.log('🔍 Verificando registro existente...');
    const [rows] = await connection.execute(
      'SELECT * FROM admin_settings WHERE `key` = ?',
      ['jsessionid']
    );

    if (rows.length > 0) {
      console.log('📝 Atualizando registro existente...');
      await connection.execute(
        'UPDATE admin_settings SET `value` = ? WHERE `key` = ?',
        [jsessionid, 'jsessionid']
      );
      console.log('✅ Registro atualizado com sucesso!');
    } else {
      console.log('📝 Inserindo novo registro...');
      await connection.execute(
        'INSERT INTO admin_settings (`key`, `value`) VALUES (?, ?)',
        ['jsessionid', jsessionid]
      );
      console.log('✅ Registro inserido com sucesso!');
    }

    return true;

  } catch (error) {
    console.error('\n❌ Erro ao acessar banco de dados:');
    console.error(`   ${error.message}`);
    return false;
  } finally {
    if (connection) {
      await connection.end();
      console.log('🔌 Conexão fechada.');
    }
  }
}

/**
 * Conecta ao Chrome via CDP e captura os cookies
 */
async function extractJSessionIdViaCDP() {
  console.log('\n' + '='.repeat(60));
  console.log('🔐 CAPTURA DE JSESSIONID - E-CRV SP (CDP)');
  console.log('='.repeat(60));

  const CDP_URL = 'http://127.0.0.1:9222';

  try {
    // 1. Listar todas as abas/targets
    console.log(`\n🔍 Conectando ao Chrome CDP: ${CDP_URL}...`);

    const response = await fetch(`${CDP_URL}/json`);
    if (!response.ok) {
      throw new Error(`CDP não respondeu: ${response.status}`);
    }

    const targets = await response.json();
    console.log(`✅ Conectado! Targets encontrados: ${targets.length}`);

    // 2. Buscar página do E-CRV
    let ecrvTarget = null;
    let ecrvWebSocketUrl = null;

    for (const target of targets) {
      console.log(`   📄 ${target.type}: ${target.url}`);
      if (target.url.includes('e-crvsp.sp.gov.br') && target.type === 'page') {
        ecrvTarget = target;
        ecrvWebSocketUrl = target.webSocketDebuggerUrl;
        console.log('   ✅ Página do E-CRV encontrada!');
        break;
      }
    }

    // Se não encontrou E-CRV, usa a primeira página
    if (!ecrvTarget) {
      const pageTarget = targets.find(t => t.type === 'page');
      if (pageTarget) {
        console.log('⚠️  E-CRV não encontrado, usando primeira página...');
        ecrvTarget = pageTarget;
        ecrvWebSocketUrl = pageTarget.webSocketDebuggerUrl;
      }
    }

    if (!ecrvWebSocketUrl) {
      throw new Error('Nenhuma página encontrada no Chrome');
    }

    // 3. Conectar via WebSocket e executar JavaScript para pegar os cookies
    console.log('\n🔌 Conectando via WebSocket...');

    // Usar o fetch para pegar cookies via HTTP endpoint do CDP
    // Isso é mais simples que WebSocket
    const targetResponse = await fetch(ecrvWebSocketUrl.replace('ws://', 'http://').replace('ws://', 'http://'), {
      headers: { 'Content-Type': 'application/json' }
    }).catch(() => null);

    // Abordagem mais simples: usar Runtime.evaluate via fetch
    // Vamos usar uma estratégia diferente - acessar diretamente o endpoint do CDP

    // Na verdade, a forma mais simples é usar fetch no endpoint /json/list
    // e depois conectar ao WebSocket. Mas como Node.js nativo não tem WebSocket,
    // vamos usar uma abordagem via fetch HTTP do CDP

    // Vamos tentar usar a API REST do CDP para executar JavaScript
    // endpoint: /runtime/evaluate

    // Método mais simples: usar fetch para chamar a API do CDP
    const sessionId = Date.now().toString();

    // Vamos usar o endpoint do CDP para executar JavaScript
    // Primeiro, precisamos do target ID
    const targetId = ecrvTarget.id;

    // Agora vamos tentar usar fetch com o protocolo CDP
    // Como não temos WebSocket nativo, vamos usar uma biblioteca ou abordagem diferente

    // Abordagem simplificada: vamos criar um script que injeta o JS via fetch
    // mas o CDP precisa de WebSocket...

    // Vamos instalar e usar uma biblioteca WebSocket simples
    console.log('📡 Usando biblioteca ws para conectar ao CDP...');

    // Importar ws dinamicamente
    let WebSocket;
    try {
      const wsModule = await import('ws');
      WebSocket = wsModule.default;
    } catch (wsError) {
      console.log('⚠️  Biblioteca "ws" não instalada. Instalando...');
      console.log('   Execute: npm install ws');
      throw new Error('Biblioteca ws não encontrada. Execute: npm install ws');
    }

    const jsessionId = await new Promise((resolve, reject) => {
      const ws = new WebSocket(ecrvWebSocketUrl);

      let messageId = 1;

      ws.on('open', () => {
        console.log('✅ WebSocket conectado!');

        // Habilitar Runtime
        ws.send(JSON.stringify({
          id: messageId++,
          method: 'Runtime.enable',
          params: {}
        }));

        // Executar JavaScript para pegar document.cookie
        ws.send(JSON.stringify({
          id: messageId++,
          method: 'Runtime.evaluate',
          params: {
            expression: 'document.cookie',
            returnByValue: true,
            awaitPromise: true
          }
        }));
      });

      ws.on('message', (data) => {
        try {
          const message = JSON.parse(data.toString());

          if (message.result) {
            if (message.result.result && message.result.result.value) {
              const cookies = message.result.result.value;
              console.log(`🍪 Cookies recebidos: ${cookies.substring(0, 100)}...`);

              const match = cookies.match(/JSESSIONID=([^;]+)/i);
              if (match) {
                console.log(`✅ JSESSIONID encontrado: ${match[1]}`);
                ws.close();
                resolve(match[1]);
                return;
              }
            }
          }

          // Se chegou aqui e não resolveu, verificar se é a última mensagem
          if (message.id === messageId - 1) {
            ws.close();
            resolve(null);
          }
        } catch (e) {
          console.log('⚠️  Erro ao parsear mensagem:', e.message);
        }
      });

      ws.on('error', (err) => {
        console.error('❌ Erro no WebSocket:', err.message);
        reject(err);
      });

      ws.on('close', () => {
        if (!jsessionId) {
          resolve(null);
        }
      });

      // Timeout
      setTimeout(() => {
        ws.close();
        resolve(null);
      }, 10000);
    });

    if (jsessionId) {
      return jsessionId;
    }

    throw new Error('Não foi possível extrair JSESSIONID');

  } catch (error) {
    console.error(`\n❌ Erro: ${error.message}`);

    if (error.message.includes('ECONNREFUSED') || error.message.includes('fetch')) {
      console.log('\n⚠️  Não foi possível conectar ao Chrome.');
      console.log('');
      console.log('💡 SOLUÇÃO:');
      console.log('   O Chrome precisa estar rodando com:');
      console.log('   chrome.exe --remote-debugging-port=9222');
      console.log('');
      console.log('   Para extrair manualmente:');
      console.log('   1. No Chrome, pressione F12');
      console.log('   2. Va em Console e digite: document.cookie');
      console.log('   3. Copie o JSESSIONID');
      console.log('   4. Execute: node save-jsessionid.js "<seu_jsessionid>"');
    }

    return null;
  }
}

/**
 * Função principal
 */
async function main() {
  try {
    const jsessionid = await extractJSessionIdViaCDP();

    if (jsessionid) {
      const saved = await saveJSessionId(jsessionid);
      if (saved) {
        console.log('\n' + '='.repeat(60));
        console.log('✅ JSESSIONID SALVO COM SUCESSO!');
        console.log('='.repeat(60) + '\n');
        process.exit(0);
      }
    }

    console.log('\n' + '='.repeat(60));
    console.log('⚠️  FALHA NA CAPTURA AUTOMÁTICA');
    console.log('='.repeat(60) + '\n');
    process.exit(1);
  } catch (error) {
    console.error('\n❌ Erro fatal:', error.message);
    console.error(error.stack);
    process.exit(1);
  }
}

main();
