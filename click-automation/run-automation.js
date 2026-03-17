import fs from 'node:fs/promises';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import os from 'node:os';

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function toBool(value, defaultValue = false) {
  if (value == null) return defaultValue;
  const v = String(value).trim().toLowerCase();
  if (v === '1' || v === 'true' || v === 'yes' || v === 'y') return true;
  if (v === '0' || v === 'false' || v === 'no' || v === 'n') return false;
  return defaultValue;
}

async function ensureDir(dir) {
  await fs.mkdir(dir, { recursive: true });
}

async function runPowerShellReplay(points, options = {}) {
  const {
    speed = 1.0,
    visualDebug = true,
    visualDebugMs = 500,
    preWaitMs = 5000,
  } = options;

  // Criar arquivo temporário com os pontos
  const tmpPointsPath = path.join(
    os.tmpdir(),
    `click-automation-points-${Date.now()}-${Math.random().toString(36).slice(2)}.json`
  );

  await fs.writeFile(tmpPointsPath, JSON.stringify(points, null, 2), 'utf-8');

  const __dirname = path.dirname(fileURLToPath(import.meta.url));
  const psScript = path.join(__dirname, 'win-replay-simple.ps1');

  const args = [
    '-NoProfile',
    '-ExecutionPolicy',
    'Bypass',
    '-File',
    psScript,
    '-PointsPath',
    tmpPointsPath,
    '-Speed',
    String(speed),
    '-VisualDebug',
    visualDebug ? 'true' : 'false',
    '-VisualDebugMs',
    String(visualDebugMs),
    '-PreWaitMs',
    String(preWaitMs),
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
    child.on('close', async (code) => {
      // Limpar arquivo temporário
      try { await fs.unlink(tmpPointsPath); } catch {}
      if (code !== 0) {
        reject(new Error(stderr || `Replay falhou (code ${code}).`));
        return;
      }
      resolve(stdout.trim());
    });
  });
}

async function main() {
  const INPUT_FILE = 'clicks.json';

  if (process.platform !== 'win32') {
    console.log('⚠️  Replay suportado apenas no Windows.\n');
    process.exit(1);
  }

  // Carregar cliques
  if (!await fs.fileExists(INPUT_FILE)) {
    console.error(`❌ Arquivo ${INPUT_FILE} não encontrado!`);
    console.log('💡 Execute primeiro: npm run capture');
    process.exit(1);
  }

  const clicks = JSON.parse(await fs.readFile(INPUT_FILE, 'utf-8'));

  if (!clicks || clicks.length === 0) {
    console.error('❌ Nenhum ponto encontrado no arquivo!');
    process.exit(1);
  }

  console.log('\n' + '='.repeat(60));
  console.log('🤖 AUTOMAÇÃO DE CLIQUES');
  console.log('='.repeat(60));
  console.log(`\n📊 ${clicks.length} pontos serão executados`);
  console.log('\n⚠️  AVISO: A automação começará em 5 segundos...');
  console.log('    Posicione a janela do Chrome/E-CRV onde necessário!\n');

  // Contagem regressiva
  for (let i = 5; i > 0; i--) {
    console.log(`   ⏱️  ${i}...`);
    await sleep(1000);
  }

  console.log('\n🚀 Iniciando automação...\n');

  // Converter formato dos cliques para o replay
  const replayEvents = [];
  let currentTime = 0;

  for (const click of clicks) {
    // Adicionar evento de mouse down
    replayEvents.push({
      type: 'mouse_down',
      x: click.x,
      y: click.y,
      button: 1, // left button
      t: currentTime,
    });
    currentTime += 100;

    // Adicionar evento de mouse up
    replayEvents.push({
      type: 'mouse_up',
      x: click.x,
      y: click.y,
      button: 1,
      t: currentTime,
    });
    currentTime += 500; // 500ms entre cliques
  }

  await runPowerShellReplay(replayEvents, {
    speed: 1.0,
    visualDebug: true,
    visualDebugMs: 300,
    preWaitMs: 1000, // 1 segundo após countdown
  });

  console.log('\n✅ Automação concluída!');
  console.log('='.repeat(60) + '\n');
}

main().catch((err) => {
  console.error(`❌ Erro: ${err.message}`);
  process.exit(1);
});
