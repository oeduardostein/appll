#!/usr/bin/env node

/**
 * Script para abrir o Chrome com uma URL específica
 */

import { spawn } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

function openChrome(url) {
  const chromePaths = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    process.env.LOCALAPPDATA + '\\Google\\Chrome\\Application\\chrome.exe',
  ];

  // Tentar encontrar Chrome
  for (const chromePath of chromePaths) {
    const ps = spawn('powershell.exe', [
      '-NoProfile',
      '-Command',
      `Test-Path "${chromePath}"`
    ], { stdio: 'pipe' });

    // Simplificado - apenas inicia o processo
  }

  // Tentar abrir usando start do Windows
  spawn('cmd', ['/c', 'start', 'chrome', url], { stdio: 'ignore', detached: true });
}

async function main() {
  const url = process.argv[2] || 'https://www.e-crvsp.sp.gov.br/';

  console.log('\n🌐 Abrindo Chrome...');
  console.log(`   URL: ${url}\n`);

  openChrome(url);

  // Aguardar um pouco para o Chrome abrir
  await new Promise(r => setTimeout(r, 3000));

  console.log('✅ Chrome aberto!\n');
}

main().catch(console.error);
