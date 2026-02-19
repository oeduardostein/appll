import fs from 'node:fs/promises';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

function parseArgs(argv) {
  const args = {
    templatePath: '',
    outputPath: '',
    help: false,
  };

  for (let i = 0; i < argv.length; i += 1) {
    const a = argv[i];
    if (a === '--help' || a === '-h') {
      args.help = true;
      continue;
    }
    if ((a === '--template' || a === '-t') && argv[i + 1]) {
      args.templatePath = argv[i + 1];
      i += 1;
      continue;
    }
    if ((a === '--out' || a === '-o') && argv[i + 1]) {
      args.outputPath = argv[i + 1];
      i += 1;
      continue;
    }
  }

  return args;
}

function usage() {
  console.log('Uso: node agent/calibrate.mjs [--template <arquivo>] [--out <arquivo>]');
  console.log('Ex:  node agent/calibrate.mjs --template recordings/template.json');
  console.log('Ex:  node agent/calibrate.mjs --template recordings/meu-template.json --out recordings/meu-template.calibrated.json');
  console.log('Se --template nao for informado, o script tenta detectar automaticamente em recordings/.');
}

async function ensureFileExists(filePath) {
  await fs.access(filePath);
}

function defaultOutputPath(templatePath) {
  const parsed = path.parse(templatePath);
  return path.join(parsed.dir, `${parsed.name}.calibrated${parsed.ext || '.json'}`);
}

async function pathExists(filePath) {
  try {
    await fs.access(filePath);
    return true;
  } catch {
    return false;
  }
}

async function detectTemplatePath(cwd) {
  const recordingsDir = path.join(cwd, 'recordings');

  const preferred = [
    path.join(recordingsDir, 'template.json'),
    path.join(recordingsDir, 'meu-template.json'),
  ];

  for (const candidate of preferred) {
    if (await pathExists(candidate)) return candidate;
  }

  let entries = [];
  try {
    entries = await fs.readdir(recordingsDir, { withFileTypes: true });
  } catch {
    throw new Error(
      `Nenhum template encontrado. Diretorio inexistente: ${recordingsDir}. Informe --template <arquivo>.`
    );
  }

  const files = entries
    .filter((entry) => entry.isFile() && entry.name.toLowerCase().endsWith('.json'))
    .map((entry) => path.join(recordingsDir, entry.name));

  if (files.length === 0) {
    throw new Error(
      `Nenhum .json encontrado em ${recordingsDir}. Informe --template <arquivo>.`
    );
  }

  const notCalibrated = files.filter((filePath) => !filePath.toLowerCase().endsWith('.calibrated.json'));
  const pool = notCalibrated.length > 0 ? notCalibrated : files;

  const templateNamed = pool.filter((filePath) => /template/i.test(path.basename(filePath)));
  const prioritized = templateNamed.length > 0 ? templateNamed : pool;

  const withStat = await Promise.all(
    prioritized.map(async (filePath) => ({
      filePath,
      stat: await fs.stat(filePath),
    }))
  );

  withStat.sort((a, b) => b.stat.mtimeMs - a.stat.mtimeMs);
  return withStat[0].filePath;
}

async function runPowerShell(scriptPath, templatePath, outputPath) {
  const args = [
    '-NoProfile',
    '-ExecutionPolicy',
    'Bypass',
    '-File',
    scriptPath,
    '-TemplatePath',
    templatePath,
    '-OutputPath',
    outputPath,
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
        reject(new Error(stderr || `Calibracao falhou (code ${code}).`));
        return;
      }
      resolve(stdout.trim());
    });
  });
}

async function main() {
  const parsedArgs = parseArgs(process.argv.slice(2));
  if (parsedArgs.help) {
    usage();
    return;
  }

  if (process.platform !== 'win32') {
    throw new Error('Calibracao visual suportada apenas no Windows.');
  }

  const templateAbs = parsedArgs.templatePath
    ? path.resolve(process.cwd(), parsedArgs.templatePath)
    : await detectTemplatePath(process.cwd());
  const outputAbs = path.resolve(
    process.cwd(),
    parsedArgs.outputPath || defaultOutputPath(templateAbs)
  );

  await ensureFileExists(templateAbs);
  await fs.mkdir(path.dirname(outputAbs), { recursive: true });

  const __dirname = path.dirname(fileURLToPath(import.meta.url));
  const psScript = path.join(__dirname, 'win-calibrate.ps1');

  console.log(`Template origem: ${templateAbs}`);
  console.log(`Template destino: ${outputAbs}`);
  console.log('Iniciando calibracao visual...');

  const resultRaw = await runPowerShell(psScript, templateAbs, outputAbs);

  let parsedResult = null;
  try {
    parsedResult = JSON.parse(resultRaw);
  } catch {
    const lines = String(resultRaw || '')
      .split(/\r?\n/)
      .map((line) => line.trim())
      .filter(Boolean);
    const lastJsonLine = [...lines].reverse().find(
      (line) => line.startsWith('{') && line.endsWith('}')
    );
    if (lastJsonLine) {
      try {
        parsedResult = JSON.parse(lastJsonLine);
      } catch {
        parsedResult = null;
      }
    }
  }

  if (parsedResult) {
    const slotsPart =
      typeof parsedResult.slotsMarked === 'number'
        ? ` slots=${parsedResult.slotsMarked}`
        : '';
    const rawPart =
      typeof parsedResult.pointsRaw === 'number'
        ? ` (originais=${parsedResult.pointsRaw})`
        : '';
    console.log(
      `Calibracao concluida. pontos=${parsedResult.pointsTotal}${rawPart} ajustados=${parsedResult.pointsChanged}${slotsPart} arquivo=${parsedResult.outputPath}`
    );
    return;
  }

  console.log('Calibracao concluida.');
}

main().catch((err) => {
  console.error(`Erro: ${err.message}`);
  process.exit(1);
});
