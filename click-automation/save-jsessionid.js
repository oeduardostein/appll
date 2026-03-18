#!/usr/bin/env node

import mysql from 'mysql2/promise';

const dbConfig = {
  host: '193.203.175.152',
  port: 3306,
  database: 'u365250089_appll',
  user: 'u365250089_appll',
  password: 'Skala@2025$',
};

async function saveApiKey(apiKey) {
  if (!apiKey) {
    console.error('❌ Erro: api_key não fornecida');
    process.exit(1);
  }

  console.log('\n' + '='.repeat(60));
  console.log('💾 SALVANDO API_KEY NO BANCO DE DADOS');
  console.log('='.repeat(60));
  console.log(`\n📋 API_KEY: ${apiKey}`);

  let connection;

  try {
    console.log('\n🔌 Conectando ao banco de dados...');
    connection = await mysql.createConnection(dbConfig);
    console.log('✅ Conectado ao banco!');

    console.log('\n🔍 Verificando registro id=1...');
    const [rows] = await connection.execute(
      'SELECT * FROM admin_settings WHERE id = ? LIMIT 1',
      [1]
    );

    if (rows.length === 0) {
      console.error('❌ Registro com id=1 não encontrado');
      process.exit(1);
    }

    console.log('📝 Atualizando apenas o primeiro registro...');
    await connection.execute(
      'UPDATE admin_settings SET `key` = ?, `value` = ?, `updated_at` = NOW() WHERE id = ?',
      ['api_key', apiKey, 1]
    );

    const [verifyRows] = await connection.execute(
      'SELECT * FROM admin_settings WHERE id = ? LIMIT 1',
      [1]
    );

    if (verifyRows.length > 0) {
      console.log('\n✅ Registro atualizado no banco:');
      console.log(`   ID: ${verifyRows[0].id}`);
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

const args = process.argv.slice(2);
const apiKey = args[0];

if (!apiKey || apiKey.startsWith('--')) {
  console.log('Uso: node save-api-key.js <api_key>');
  console.log('');
  console.log('Exemplo: node save-api-key.js "ABC123XYZ..."');
  process.exit(1);
}

saveApiKey(apiKey).catch((err) => {
  console.error(`❌ Erro: ${err.message}`);
  process.exit(1);
});