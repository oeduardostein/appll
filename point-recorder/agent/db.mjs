import mysql from 'mysql2/promise';

export function getMysqlConfigFromEnv(env) {
  const host = env.DB_HOST || '127.0.0.1';
  const port = Number(env.DB_PORT || 3306);
  const database = env.DB_DATABASE || '';
  const user = env.DB_USERNAME || '';
  const password = env.DB_PASSWORD || '';
  const parsedConnectionLimit = Number(env.DB_POOL_CONNECTION_LIMIT || 2);
  const connectionLimit = Number.isFinite(parsedConnectionLimit) && parsedConnectionLimit > 0
    ? Math.floor(parsedConnectionLimit)
    : 2;
  const parsedQueueLimit = Number(env.DB_POOL_QUEUE_LIMIT || 0);
  const queueLimit = Number.isFinite(parsedQueueLimit) && parsedQueueLimit >= 0
    ? Math.floor(parsedQueueLimit)
    : 0;

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
    connectionLimit,
    queueLimit,
    namedPlaceholders: true,
    dateStrings: false,
    timezone: 'Z',
  };
}

export async function createPool(env) {
  const cfg = getMysqlConfigFromEnv(env);
  return mysql.createPool(cfg);
}
