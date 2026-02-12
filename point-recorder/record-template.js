/* eslint-disable no-console */

// Gravador "template": igual ao record.js, mas permite marcar "slots" (CPF/NOME/CHASSI)
// sem precisar regravar tudo depois. Na reprodução, os textos serão substituídos pelos
// valores vindos do banco.
//
// Hotkeys:
// - F6  => slot_begin cpf_cgc
// - F7  => slot_begin nome
// - F8  => slot_begin chassi
// - F12 => screenshot (ponto onde o replay deve tirar print)

const fs = require('node:fs');
const path = require('node:path');

function parseArgs(argv) {
  const args = { out: 'recordings/template.json', format: 'json', help: false };

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

function printHelp() {
  console.log('Uso: node record-template.js [--out <arquivo>] [--format json|jsonl]');
  console.log('Ex:  node record-template.js --out recordings/template.json --format json');
  console.log('Ex:  node record-template.js --out recordings/template.jsonl --format jsonl');
  console.log('');
  console.log('Hotkeys durante a gravação:');
  console.log('- F6  => marcar slot CPF/CNPJ');
  console.log('- F7  => marcar slot Nome');
  console.log('- F8  => marcar slot Chassi');
  console.log('- F12 => marcar ponto de screenshot');
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
console.log('Modo TEMPLATE (slots + screenshot).');
console.log(`Arquivo: ${outPath}`);
console.log(`Formato: ${format}`);
console.log('Parar: Ctrl+C');

function formatLine(fmt, record) {
  if (fmt === 'jsonl') return JSON.stringify(record);
  throw new Error(`formatLine não suporta formato: ${fmt}`);
}

function writeRecord(record) {
  if (format === 'jsonl') {
    stream.write(formatLine(format, record) + '\n');
    return;
  }

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

let lastMouse = { x: null, y: null };

function recordSlot(name) {
  writeRecord({
    ...baseRecord('slot_begin'),
    name
  });
  console.log(`slot_begin: ${name}`);
}

function recordScreenshot() {
  writeRecord({
    ...baseRecord('screenshot'),
    x: lastMouse.x,
    y: lastMouse.y
  });
  console.log('screenshot: marcado');
}

hook.on('mousedown', (event) => {
  lastMouse = { x: event.x, y: event.y };
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
  lastMouse = { x: event.x, y: event.y };
  const record = {
    ...baseRecord('mouse_up'),
    x: event.x,
    y: event.y,
    button: event.button
  };
  writeRecord(record);
});

hook.on('keydown', (event) => {
  // uiohook keycodes (mais comuns)
  // F6  = 64, F7  = 65, F8  = 66, F12 = 70
  // Esses valores podem variar; por isso também tentamos rawcode quando existir.
  const keycode = event.keycode;
  const rawcode = event.rawcode ?? null;

  const isF6 = keycode === 64 || rawcode === 0x75;
  const isF7 = keycode === 65 || rawcode === 0x76;
  const isF8 = keycode === 66 || rawcode === 0x77;
  const isF12 = keycode === 70 || rawcode === 0x7b;

  if (isF6) return recordSlot('cpf_cgc');
  if (isF7) return recordSlot('nome');
  if (isF8) return recordSlot('chassi');
  if (isF12) return recordScreenshot();

  const char =
    typeof event.keychar === 'number' && event.keychar > 0 ? String.fromCharCode(event.keychar) : null;

  const record = {
    ...baseRecord('key_down'),
    keycode,
    rawcode,
    keychar: typeof event.keychar === 'number' ? event.keychar : null,
    char,
    shift: typeof event.shiftKey === 'boolean' ? event.shiftKey : null,
    ctrl: typeof event.ctrlKey === 'boolean' ? event.ctrlKey : null,
    alt: typeof event.altKey === 'boolean' ? event.altKey : null,
    meta: typeof event.metaKey === 'boolean' ? event.metaKey : null,
    mask: typeof event.mask === 'number' ? event.mask : null
  };

  writeRecord(record);
});

hook.on('keypress', (event) => {
  const char =
    typeof event.keychar === 'number' && event.keychar > 0 ? String.fromCharCode(event.keychar) : null;

  const record = {
    ...baseRecord('key_press'),
    keycode: event.keycode,
    rawcode: event.rawcode ?? null,
    keychar: typeof event.keychar === 'number' ? event.keychar : null,
    char,
    shift: typeof event.shiftKey === 'boolean' ? event.shiftKey : null,
    ctrl: typeof event.ctrlKey === 'boolean' ? event.ctrlKey : null,
    alt: typeof event.altKey === 'boolean' ? event.altKey : null,
    meta: typeof event.metaKey === 'boolean' ? event.metaKey : null,
    mask: typeof event.mask === 'number' ? event.mask : null
  };

  writeRecord(record);
});

hook.on('keyup', (event) => {
  const record = {
    ...baseRecord('key_up'),
    keycode: event.keycode,
    rawcode: event.rawcode ?? null,
    keychar: typeof event.keychar === 'number' ? event.keychar : null,
    shift: typeof event.shiftKey === 'boolean' ? event.shiftKey : null,
    ctrl: typeof event.ctrlKey === 'boolean' ? event.ctrlKey : null,
    alt: typeof event.altKey === 'boolean' ? event.altKey : null,
    meta: typeof event.metaKey === 'boolean' ? event.metaKey : null,
    mask: typeof event.mask === 'number' ? event.mask : null
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

