import fs from 'node:fs/promises';
import path from 'node:path';

const LEVELS = {
  debug: 10,
  info: 20,
  warn: 30,
  error: 40,
};

function toBool(value, defaultValue = true) {
  if (value == null) return defaultValue;
  const v = String(value).trim().toLowerCase();
  if (v === '1' || v === 'true' || v === 'yes' || v === 'y') return true;
  if (v === '0' || v === 'false' || v === 'no' || v === 'n') return false;
  return defaultValue;
}

function normalizeLevel(level) {
  const v = String(level || 'info').trim().toLowerCase();
  return LEVELS[v] ? v : 'info';
}

function buildLine(level, message, meta) {
  const ts = new Date().toISOString();
  const metaStr =
    meta && Object.keys(meta).length
      ? ` ${JSON.stringify(meta)}`
      : '';
  return `[${ts}] [${level}] ${message}${metaStr}`;
}

async function ensureDir(dir) {
  await fs.mkdir(dir, { recursive: true });
}

export function createLogger(env = process.env) {
  const level = normalizeLevel(env.AGENT_LOG_LEVEL || 'info');
  const logFile = env.AGENT_LOG_FILE || 'logs/agent.log';
  const consoleEnabled = toBool(env.AGENT_LOG_CONSOLE, true);

  const absPath = path.resolve(process.cwd(), logFile);
  const ready = ensureDir(path.dirname(absPath)).catch(() => {});

  const write = async (lvl, message, meta) => {
    if (LEVELS[lvl] < LEVELS[level]) return;
    const line = buildLine(lvl, message, meta);
    if (consoleEnabled) {
      if (lvl === 'error') console.error(line);
      else if (lvl === 'warn') console.warn(line);
      else console.log(line);
    }
    await ready;
    await fs.appendFile(absPath, line + '\n');
  };

  return {
    debug: (msg, meta) => write('debug', msg, meta),
    info: (msg, meta) => write('info', msg, meta),
    warn: (msg, meta) => write('warn', msg, meta),
    error: (msg, meta) => write('error', msg, meta),
  };
}
