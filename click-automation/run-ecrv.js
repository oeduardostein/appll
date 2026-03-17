#!/usr/bin/env node

/**
 * Script para executar a automação do E-CRV SP
 *
 * Uso:
 *   node run-ecrv.js [--cpf <cpf>] [--pin <pin>]
 */

import { spawn } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

function parseArgs(argv) {
  const args = {
    cpf: '44922011811',
    pin: '1234',
    preWait: 3000,
    help: false,
    noChrome: false, // Não abrir Chrome (já está aberto)
  };

  for (let i = 0; i < argv.length; i += 1) {
    const a = argv[i];
    if (a === '--help' || a === '-h') {
      args.help = true;
      continue;
    }
    if ((a === '--cpf') && argv[i + 1]) {
      args.cpf = argv[i + 1];
      i += 1;
      continue;
    }
    if ((a === '--pin') && argv[i + 1]) {
      args.pin = argv[i + 1];
      i += 1;
      continue;
    }
    if ((a === '--wait') && argv[i + 1]) {
      args.preWait = Number(argv[i + 1]);
      i += 1;
      continue;
    }
    if (a === '--no-chrome') {
      args.noChrome = true;
      continue;
    }
  }

  return args;
}

function usage() {
  console.log('Uso: node run-ecrv.js [opcoes]');
  console.log('');
  console.log('Opcoes:');
  console.log('  --cpf <cpf>       CPF para login (default: 44922011811)');
  console.log('  --pin <pin>       PIN para login (default: 1234)');
  console.log('  --wait <ms>       Tempo de espera antes de iniciar (default: 3000)');
  console.log('  --help, -h        Mostra esta ajuda');
  console.log('');
  console.log('Exemplos:');
  console.log('  node run-ecrv.js');
  console.log('  node run-ecrv.js --cpf 12345678901 --pin 4321');
}

async function main() {
  const args = parseArgs(process.argv.slice(2));
  if (args.help) {
    usage();
    return;
  }

  const psScript = path.join(__dirname, 'run-ecrv-flow.ps1');

  console.log('\n' + '='.repeat(60));
  console.log('🤖 E-CRV SP - AUTOMAÇÃO DE LOGIN');
  console.log('='.repeat(60));
  console.log(`\n📋 CPF: ${args.cpf}`);
  console.log(`🔑 PIN: ${args.pin}`);
  console.log(`\n⚠️  A automação começará em ${args.preWait / 1000} segundos...`);
  console.log('    Posicione o Chrome/E-CRV onde necessário!\n');
  console.log('='.repeat(60) + '\n');

  // Contagem regressiva
  for (let i = args.preWait / 1000; i > 0; i--) {
    console.log(`   ⏱️  ${i}...`);
    await new Promise(r => setTimeout(r, 1000));
  }

  console.log('\n🚀 Iniciando automação...\n');

  const psArgs = [
    '-NoProfile',
    '-ExecutionPolicy',
    'Bypass',
    '-File',
    psScript,
    '-FlowFile',
    'e-crv-flow.json',
    '-Cpf',
    args.cpf,
    '-Pin',
    args.pin,
    '-PreWaitMs',
    '0', // Já fizemos countdown
  ];

  // Só passar URL se não estiver no modo --no-chrome
  if (!args.noChrome) {
    psArgs.push('-Url', 'https://www.e-crvsp.sp.gov.br/');
  }

  const ps = spawn('powershell.exe', psArgs, {
    stdio: 'inherit',
    cwd: __dirname,
  });

  ps.on('exit', (code) => {
    console.log('\n' + '='.repeat(60));
    if (code === 0) {
      console.log('✅ Automação concluída com sucesso!');
    } else {
      console.log('⚠️  Automação finalizada com código:', code);
    }
    console.log('='.repeat(60) + '\n');
    process.exit(code);
  });
}

main().catch((err) => {
  console.error(`❌ Erro: ${err.message}`);
  process.exit(1);
});
