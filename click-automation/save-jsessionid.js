#!/usr/bin/env node

/**
 * Script para capturar o jsessionid do Chrome e salvar no banco MySQL
 *
 * Uso:
 *   node save-jsessionid.js <jsessionid>
 */

import mysql from 'mysql2/promise';
import { fileURLToPath } from 'url';
import path from 'path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

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
    process.exit(1);
  }

  console.log('\n' + '='.repeat(60));
  console.log('💾 SALVANDO JSESSIONID NO BANCO DE DADOS');
  console.log('='.repeat(60));
  console.log(`\n📋 JSESSIONID: ${jsessionid}`);

  let connection;
  try {
    // Conectar ao banco
    console.log('\n🔌 Conectando ao banco de dados...');
    connection = await mysql.createConnection(dbConfig);
    console.log('✅ Conectado ao banco!');

    // Verificar se o registro já existe
    console.log('\n🔍 Verificando registro existente...');
    const [rows] = await connection.execute(
      'SELECT * FROM admin_settings WHERE `key` = ?',
      ['jsessionid']
    );

    if (rows.length > 0) {
      // Atualizar registro existente
      console.log('📝 Atualizando registro existente...');
      await connection.execute(
        'UPDATE admin_settings SET `value` = ? WHERE `key` = ?',
        [jsessionid, 'jsessionid']
      );
      console.log('✅ Registro atualizado com sucesso!');
    } else {
      // Inserir novo registro
      console.log('📝 Inserindo novo registro...');
      await connection.execute(
        'INSERT INTO admin_settings (`key`, `value`) VALUES (?, ?)',
        ['jsessionid', jsessionid]
      );
      console.log('✅ Registro inserido com sucesso!');
    }

    // Verificar se foi salvo corretamente
    const [verifyRows] = await connection.execute(
      'SELECT * FROM admin_settings WHERE `key` = ?',
      ['jsessionid']
    );

    if (verifyRows.length > 0) {
      console.log('\n✅ JSESSIONID salvo no banco:');
      console.log(`   Key: ${verifyRows[0].key}`);
      console.log(`   Value: ${verifyRows[0].value}`);
    }

  } catch (error) {
    console.error('\n❌ Erro ao acessar banco de dados:');
    console.error(`   ${error.message}`);
    process.exit(1);
  } finally {
    if (connection) {
      await connection.end();
      console.log('\n🔌 Conexão fechada.');
    }
  }

  console.log('\n' + '='.repeat(60));
  console.log('✅ OPERAÇÃO CONCLUÍDA!');
  console.log('='.repeat(60) + '\n');
}

// Capturar argumentos da linha de comando
const args = process.argv.slice(2);
const jsessionid = args[0];

if (!jsessionid || jsessionid.startsWith('--')) {
  console.log('Uso: node save-jsessionid.js <jsessionid>');
  console.log('');
  console.log('Exemplo: node save-jsessionid.js "ABC123XYZ..."');
  process.exit(1);
}

saveJSessionId(jsessionid).catch((err) => {
  console.error(`❌ Erro: ${err.message}`);
  process.exit(1);
});
