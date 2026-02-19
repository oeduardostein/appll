import fs from 'node:fs/promises';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

function parseArgs(argv) {
  const args = {
    out: 'recordings/manual-points.json',
    cardMs: 1200,
    pollMs: 20,
    help: false,
  };

  for (let i = 0; i < argv.length; i += 1) {
    const a = argv[i];
    if (a === '--help' || a === '-h') {
      args.help = true;
      continue;
    }
    if ((a === '--out' || a === '-o') && argv[i + 1]) {
      args.out = argv[i + 1];
      i += 1;
      continue;
    }
    if (a === '--card-ms' && argv[i + 1]) {
      args.cardMs = Number(argv[i + 1]);
      i += 1;
      continue;
    }
    if (a === '--poll-ms' && argv[i + 1]) {
      args.pollMs = Number(argv[i + 1]);
      i += 1;
      continue;
    }
  }

  return args;
}

function usage() {
  console.log('Uso: node agent/pick-points.mjs [--out <arquivo>] [--card-ms <ms>] [--poll-ms <ms>]');
  console.log('Ex:  node agent/pick-points.mjs --out recordings/manual-points.json');
  console.log('Ex:  node agent/pick-points.mjs --out recordings/pontos-login.json --card-ms 1500');
}

async function runPowerShell(scriptPath, outPath, cardMs, pollMs) {
  const args = [
    '-NoProfile',
    '-ExecutionPolicy',
    'Bypass',
    '-File',
    scriptPath,
    '-OutputPath',
    outPath,
    '-CardMs',
    String(Number.isFinite(cardMs) ? cardMs : 1200),
    '-PollMs',
    String(Number.isFinite(pollMs) ? pollMs : 20),
  ];

  return new Promise((resolve, reject) => {
    const child = spawn('powershell.exe', args, { windowsHide: false });
    let stdout = '';
    let stderr = '';

    child.stdout.on('data', (d) => {
      const text = d.toString();
      stdout += text;
      process.stdout.write(text);
    });
    child.stderr.on('data', (d) => {
      const text = d.toString();
      stderr += text;
      process.stderr.write(text);
    });
    child.on('error', reject);
    child.on('close', (code) => {
      if (code !== 0) {
        reject(new Error(stderr || `Point picker falhou (code ${code}).`));
        return;
      }
      resolve(stdout.trim());
    });
  });
}

async function main() {
  const args = parseArgs(process.argv.slice(2));
  if (args.help) {
    usage();
    return;
  }

  if (process.platform !== 'win32') {
    throw new Error('Point picker suportado apenas no Windows.');
  }

  const outAbs = path.resolve(process.cwd(), args.out);
  await fs.mkdir(path.dirname(outAbs), { recursive: true });

  const __dirname = path.dirname(fileURLToPath(import.meta.url));
  const psScript = path.join(__dirname, 'win-pick-points.ps1');

  console.log(`Saida: ${outAbs}`);
  console.log('Iniciando point picker...');

  const raw = await runPowerShell(psScript, outAbs, args.cardMs, args.pollMs);

  let parsed = null;
  try {
    parsed = raw ? JSON.parse(raw) : null;
  } catch {
    const lines = String(raw || '')
      .split(/\r?\n/)
      .map((line) => line.trim())
      .filter(Boolean);
    const lastJsonLine = [...lines].reverse().find((line) => line.startsWith('{') && line.endsWith('}'));
    if (lastJsonLine) {
      try {
        parsed = JSON.parse(lastJsonLine);
      } catch {
        parsed = null;
      }
    }
  }

  if (parsed) {
    console.log(`Concluido. pontos=${parsed.pointsTotal} arquivo=${parsed.outputPath}`);
    return;
  }

  console.log('Concluido.');
}

main().catch((err) => {
  console.error(`Erro: ${err.message}`);
  process.exit(1);
});
