import fs from 'node:fs/promises';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

function parseArgs(argv) {
  const args = {
    out: 'clicks.json',
    cardMs: 1200,
    pollMs: 20,
    url: '',
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
    if ((a === '--url' || a === '-u') && argv[i + 1]) {
      args.url = argv[i + 1];
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
  console.log('Uso: node capture-clicks.js [opcoes]');
  console.log('');
  console.log('Opcoes:');
  console.log('  --out, -o <arquivo>    Arquivo de saida (default: clicks.json)');
  console.log('  --url, -u <url>        URL para abrir no Chrome');
  console.log('  --card-ms <ms>         Tempo para mostrar card (default: 1200)');
  console.log('  --poll-ms <ms>         Intervalo de poll (default: 20)');
  console.log('  --help, -h             Mostra esta ajuda');
  console.log('');
  console.log('Exemplos:');
  console.log('  node capture-clicks.js');
  console.log('  node capture-clicks.js --url "https://ecrv.sp.gov.br"');
  console.log('  node capture-clicks.js -u "https://google.com" -o pontos.json');
}

async function runPowerShell(scriptPath, outPath, cardMs, pollMs, url) {
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

  if (url) {
    args.push('-OpenUrl', url);
  }

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
    console.log('⚠️  Point picker suportado apenas no Windows.');
    console.log('💡 No Linux, use xdotool ou ferramentas similares.\n');
    process.exit(1);
  }

  const outAbs = path.resolve(process.cwd(), args.out);
  await fs.mkdir(path.dirname(outAbs), { recursive: true });

  const __dirname = path.dirname(fileURLToPath(import.meta.url));
  const psScript = path.join(__dirname, 'win-pick-points.ps1');

  console.log('\n' + '='.repeat(60));
  console.log('🖱️  CAPTURA DE CLIQUES');
  console.log('='.repeat(60));
  console.log(`\n📂 Saída: ${outAbs}`);
  if (args.url) {
    console.log(`🌐 URL: ${args.url}`);
  }
  console.log('\nInstruções:');
  console.log('  • Clique com o botão ESQUERDO do mouse para capturar um ponto');
  console.log('  • Pressione ESC para finalizar e salvar');
  console.log('  • Uma janela mostrará os pontos capturados em tempo real\n');
  console.log('='.repeat(60));

  // Abrir Chrome ANTES do point picker se URL fornecida
  if (args.url) {
    console.log('\n🌐 Abrindo Chrome...\n');
    await new Promise((resolve) => {
      const chrome = spawn('cmd', ['/c', 'start', 'chrome', args.url], {
        stdio: 'ignore',
        detached: true
      });
      chrome.unref();
      setTimeout(resolve, 3000);
    });
    console.log('✅ Chrome aberto!\n');
  }

  console.log('Iniciando point picker...\n');

  const raw = await runPowerShell(psScript, outAbs, args.cardMs, args.pollMs, args.url);

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

  console.log('\n' + '='.repeat(60));
  if (parsed) {
    console.log(`✅ Concluído! ${parsed.pointsTotal} pontos capturados`);
    console.log(`📁 Arquivo: ${parsed.outputPath}`);
  } else {
    console.log('✅ Concluído!');
  }
  console.log('='.repeat(60) + '\n');

  // Mostrar resumo
  if (await fs.fileExists(outAbs)) {
    const clicks = JSON.parse(await fs.readFile(outAbs, 'utf-8'));
    if (clicks.length > 0) {
      console.log('📋 Resumo dos pontos capturados:');
      console.log('-'.repeat(60));
      clicks.forEach((c, i) => {
        console.log(`   ${i + 1}. X: ${String(c.x).padStart(4)}, Y: ${String(c.y).padStart(4)}`);
      });
      console.log('-'.repeat(60) + '\n');
    }
  }
}

main().catch((err) => {
  console.error(`❌ Erro: ${err.message}`);
  process.exit(1);
});
