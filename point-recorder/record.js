/* eslint-disable no-console */

const fs = require('node:fs');
const path = require('node:path');

function parseArgs(argv) {
  const args = { out: 'recordings/session.json', format: 'json', help: false };

  for (let i = 2; i < argv.length; i++) {
    const raw = argv[i];
    if (raw === '-h' || raw === '--help') {
      args.help = true;
      continue;
    }
    if (!raw.startsWith('--')) continue;

    const [key, inlineValue] = raw.slice(2).split('=');
    const next = inlineValue ?? argv[i + 1];
    const hasSeparateValue = inlineValue == null && next && !next.startsWith('--');

    if (key === 'out') {
      args.out = inlineValue ?? (hasSeparateValue ? next : args.out);
      if (hasSeparateValue) i++;
    } else if (key === 'format') {
      args.format = (inlineValue ?? (hasSeparateValue ? next : args.format)).toLowerCase();
      if (hasSeparateValue) i++;
    }
  }

  return args;
}

function ensureDirForFile(filePath) {
  fs.mkdirSync(path.dirname(filePath), { recursive: true });
}

function fileIsEmpty(filePath) {
  try {
    return fs.statSync(filePath).size === 0;
  } catch {
    return true;
  }
}

function getHook() {
  let mod;
  try {
    mod = require('uiohook-napi');
  } catch (err) {
    console.error('Falha ao carregar uiohook-napi. Rode `npm install` dentro de point-recorder.');
    console.error(String(err?.message ?? err));
    process.exit(1);
  }

  const hook = mod.uIOhook || mod.uiohook || mod;
  return { hook };
}

function formatLine(format, record) {
  if (format === 'jsonl') return JSON.stringify(record);
  throw new Error(`formatLine não suporta formato: ${format}`);
}

function printHelp() {
  console.log('Uso: node record.js [--out <arquivo>] [--format json|jsonl]');
  console.log('Ex:  node record.js --out recordings/sessao.json --format json');
  console.log('Ex:  node record.js --out recordings/sessao.jsonl --format jsonl');
}

const { out, format, help } = parseArgs(process.argv);

if (help) {
  printHelp();
  process.exit(0);
}

if (format !== 'json' && format !== 'jsonl') {
  console.error(`Formato inválido: ${format}`);
  printHelp();
  process.exit(1);
}

const outPath = path.resolve(process.cwd(), out);
ensureDirForFile(outPath);

if (format === 'json' && !fileIsEmpty(outPath)) {
  console.error('Para --format json, o arquivo precisa estar vazio (ou não existir).');
  console.error('Motivo: o gravador cria um único array JSON do começo ao fim.');
  console.error(`Arquivo: ${outPath}`);
  process.exit(1);
}

const stream = fs.createWriteStream(outPath, { flags: 'a' });
stream.on('error', (err) => {
  console.error('Erro ao escrever no arquivo:', outPath);
  console.error(String(err?.message ?? err));
  process.exit(1);
});

const { hook } = getHook();
const sessionStartedAt = Date.now();

let jsonArrayFirst = true;
let jsonClosed = false;
if (format === 'json') {
  stream.write('[\n');
}

console.log('Gravando cliques do mouse...');
console.log('Gravando teclado...');
console.log(`Arquivo: ${outPath}`);
console.log(`Formato: ${format}`);
console.log('Parar: Ctrl+C');

function writeRecord(record) {
  if (format === 'jsonl') {
    stream.write(formatLine(format, record) + '\n');
    return;
  }

  // format === 'json' => grava como array JSON (streaming)
  const prefix = jsonArrayFirst ? '' : ',\n';
  jsonArrayFirst = false;
  stream.write(prefix + JSON.stringify(record));
}

function baseRecord(type) {
  const now = Date.now();
  return {
    type,
    ts: new Date(now).toISOString(),
    t: now - sessionStartedAt
  };
}

hook.on('mousedown', (event) => {
  const record = {
    ...baseRecord('mouse_down'),
    x: event.x,
    y: event.y,
    button: event.button
  };

  writeRecord(record);
  console.log(`mouse_down: x=${record.x} y=${record.y} button=${record.button}`);
});

hook.on('mouseup', (event) => {
  const record = {
    ...baseRecord('mouse_up'),
    x: event.x,
    y: event.y,
    button: event.button
  };

  writeRecord(record);
});

hook.on('keydown', (event) => {
  const char =
    typeof event.keychar === 'number' && event.keychar > 0 ? String.fromCharCode(event.keychar) : null;

  const record = {
    ...baseRecord('key_down'),
    keycode: event.keycode,
    rawcode: event.rawcode ?? null,
    keychar: typeof event.keychar === 'number' ? event.keychar : null,
    char
  };

  writeRecord(record);
});

hook.on('keyup', (event) => {
  const record = {
    ...baseRecord('key_up'),
    keycode: event.keycode,
    rawcode: event.rawcode ?? null,
    keychar: typeof event.keychar === 'number' ? event.keychar : null
  };

  writeRecord(record);
});

hook.start();

function shutdown() {
  if (shutdown.called) return;
  shutdown.called = true;

  try {
    hook.stop();
  } catch {
    // ignore
  }

  if (format === 'json' && !jsonClosed) {
    jsonClosed = true;
    stream.write('\n]\n');
  }

  stream.end(() => {
    process.exit(0);
  });
}
shutdown.called = false;

process.on('beforeExit', () => {
  if (format === 'json' && !jsonClosed) {
    jsonClosed = true;
    stream.write('\n]\n');
  }
});

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
