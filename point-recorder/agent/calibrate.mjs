import fs from 'node:fs/promises';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

function parseArgs(argv) {
  const args = {
    templatePath: 'recordings/template.json',
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
  console.log('Ex:  node agent/calibrate.mjs --template recordings/template.json --out recordings/template.calibrated.json');
}

async function ensureFileExists(filePath) {
  await fs.access(filePath);
}

function defaultOutputPath(templatePath) {
  const parsed = path.parse(templatePath);
  return path.join(parsed.dir, `${parsed.name}.calibrated${parsed.ext || '.json'}`);
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
  if (process.platform !== 'win32') {
    throw new Error('Calibracao visual suportada apenas no Windows.');
  }

  const parsedArgs = parseArgs(process.argv.slice(2));
  if (parsedArgs.help) {
    usage();
    return;
  }

  const templateAbs = path.resolve(process.cwd(), parsedArgs.templatePath);
  const outputAbs = path.resolve(
    process.cwd(),
    parsedArgs.outputPath || defaultOutputPath(parsedArgs.templatePath)
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
    parsedResult = null;
  }

  if (parsedResult) {
    console.log(
      `Calibracao concluida. pontos=${parsedResult.pointsTotal} ajustados=${parsedResult.pointsChanged} arquivo=${parsedResult.outputPath}`
    );
    return;
  }

  console.log('Calibracao concluida.');
}

main().catch((err) => {
  console.error(`Erro: ${err.message}`);
  process.exit(1);
});

