import mysql from 'mysql2/promise';

export function getMysqlConfigFromEnv(env) {
  const host = env.DB_HOST || '127.0.0.1';
  const port = Number(env.DB_PORT || 3306);
  const database = env.DB_DATABASE || '';
  const user = env.DB_USERNAME || '';
  const password = env.DB_PASSWORD || '';

  if (!database || !user) {
    throw new Error('Config MySQL inválida: defina DB_DATABASE e DB_USERNAME no .env');
  }

  return {
    host,
    port,
    database,
    user,
    password,
    waitForConnections: true,
    connectionLimit: 5,
    queueLimit: 0,
    namedPlaceholders: true,
    dateStrings: false,
    timezone: 'Z',
  };
}

export async function createPool(env) {
  const cfg = getMysqlConfigFromEnv(env);
  return mysql.createPool(cfg);
}

