async function saveJSessionId(jsessionid) {
  if (!jsessionid) {
    log('WARN', 'Nenhum JSESSIONID para salvar');
    return false;
  }

  let connection;
  try {
    connection = await mysql.createConnection(dbConfig);
    log('INFO', 'Conexão com banco aberta');

    // 1. Verifica se já existe
    const [existing] = await connection.execute(
      'SELECT value, admin_id FROM admin_settings WHERE `key` = ? LIMIT 1',
      ['jsessionid']
    );

    if (existing.length > 0) {
      const current = existing[0];
      if (current.value !== jsessionid) {
        await connection.execute(
          'UPDATE admin_settings SET `value` = ? WHERE `key` = ?',
          [jsessionid, 'jsessionid']
        );
        log('INFO', `JSESSIONID atualizado (admin_id existente = ${current.admin_id || 'desconhecido'})`);
      } else {
        log('INFO', 'JSESSIONID igual ao atual → sem necessidade de update');
      }
      return true;
    }

    // 2. INSERT novo – versão com debug + tentativa com mais campos comuns
    log('INFO', 'Tentando INSERT novo com admin_id = ' + ADMIN_ID);

    // Tente primeiro a versão mínima
    try {
      await connection.execute(
        'INSERT INTO admin_settings (`key`, `value`, `admin_id`) VALUES (?, ?, ?)',
        ['jsessionid', jsessionid, ADMIN_ID]
      );
      log('INFO', `Sucesso: JSESSIONID inserido (admin_id = ${ADMIN_ID})`);
      return true;
    } catch (innerError) {
      log('WARN', 'INSERT mínimo falhou: ' + innerError.message);

      // Tentativa 2: com campos comuns que muitos sistemas exigem
      try {
        await connection.execute(
          `INSERT INTO admin_settings 
             (\`key\`, \`value\`, \`admin_id\`, \`created_at\`, \`updated_at\`) 
           VALUES (?, ?, ?, NOW(), NOW())`,
          ['jsessionid', jsessionid, ADMIN_ID]
        );
        log('INFO', `Sucesso na tentativa 2 (com created_at/updated_at)`);
        return true;
      } catch (innerError2) {
        log('ERROR', 'Tentativa 2 também falhou: ' + innerError2.message);
        throw innerError2; // relança para log principal
      }
    }

  } catch (error) {
    log('ERROR', `Falha completa ao salvar JSESSIONID: ${error.message}`);
    if (error.code) {
      log('ERROR', `Código MySQL: ${error.code} - ${error.sqlMessage || ''}`);
    }
    return false;
  } finally {
    if (connection) {
      await connection.end().catch(() => {});
      log('INFO', 'Conexão com banco fechada');
    }
  }
}