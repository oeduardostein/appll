#!/usr/bin/env node

/**
 * Script para consulta de placa usando a API SH Sistemas
 *
 * Fluxo:
 * 1. Decodificar imagem do captcha via DecodeImagem100
 * 2. Consultar placa via ChkRetorno2HTML usando o captcha decodificado
 */

const https = require('https');
const http = require('http');
const fs = require('fs');
const path = require('path');

// Configurações
const CONFIG = {
  host: 'londres.shsistemas.com.br',
  auth: 'Basic U0Rlc3AyMDE4OnBzd1NEZXNwLTIwMTg=', // Base64 de SDesp2018:pswSDesp-2018
  usuario: '52299',
  // Estes valores podem precisar ser ajustados
  serie: 'P1400',
  nome: 'LUCAS'
};

/**
 * Faz uma requisição HTTP/HTTPS
 */
function request(options) {
  return new Promise((resolve, reject) => {
    const protocol = options.port === 443 ? https : http;
    const req = protocol.request(options, (res) => {
      let data = '';

      res.on('data', (chunk) => {
        data += chunk;
      });

      res.on('end', () => {
        if (res.statusCode >= 200 && res.statusCode < 300) {
          resolve({ statusCode: res.statusCode, data, headers: res.headers });
        } else {
          reject(new Error(`HTTP ${res.statusCode}: ${data}`));
        }
      });
    });

    req.on('error', reject);

    if (options.body) {
      req.write(options.body);
    }

    req.end();
  });
}

/**
 * Decodifica imagem do captcha
 * @param {string|Buffer} imagePath - Caminho da imagem ou Buffer de bytes
 */
async function decodificarCaptcha(imagePath) {
  console.log('Decodificando captcha...');

  let imageBytes;
  if (Buffer.isBuffer(imagePath)) {
    imageBytes = imagePath;
  } else {
    // Lê o arquivo BMP
    const imageBuffer = fs.readFileSync(imagePath);
    // Converte para array de inteiros
    imageBytes = Array.from(imageBuffer);
  }

  const options = {
    hostname: CONFIG.host,
    port: 80,
    path: '/ecrv_server/datasnap/rest/TSM/%22DecodeImagem100%22/@SDesp/',
    method: 'POST',
    headers: {
      'Content-Type': 'text/plain;charset=UTF-8',
      'Accept': 'application/JSON',
      'Authorization': CONFIG.auth,
      'If-Modified-Since': 'Mon, 1 Oct 1990 05:00:00 GMT',
      'User-Agent': 'Embarcadero URI Client/1.0'
    },
    body: JSON.stringify(imageBytes)
  };

  const response = await request(options);

  // A resposta vem como base64 de um JSON
  const decoded = JSON.parse(response.data);
  const captcha = decoded.result[0];

  console.log(`Captcha decodificado: ${captcha}`);
  return captcha;
}

/**
 * Consulta placa usando o captcha decodificado
 * @param {string} placa - Placa a ser consultada (ex: "ABC1234" ou "ABC1D23")
 * @param {string} captcha - Captcha decodificado
 */
async function consultarPlaca(placa, captcha) {
  console.log(`Consultando placa: ${placa}`);

  // Formata a URL com os parâmetros
  const path = `/ecrv_server/datasnap/rest/TSM/%22ChkRetorno2HTML%22/@SDesp/${CONFIG.usuario}/${placa}/${CONFIG.serie}/${captcha}/`;

  const options = {
    hostname: CONFIG.host,
    port: 80,
    path,
    method: 'GET',
    headers: {
      'Content-Type': 'text/plain;charset=UTF-8',
      'Accept': 'application/JSON',
      'Authorization': CONFIG.auth,
      'If-Modified-Since': 'Mon, 1 Oct 1990 05:00:00 GMT',
      'User-Agent': 'Embarcadero URI Client/1.0'
    }
  };

  const response = await request(options);

  // Decodifica a resposta
  const decoded = JSON.parse(response.data);
  const resultado = decoded.result[0];

  return resultado;
}

/**
 * Função principal
 */
async function main() {
  const args = process.argv.slice(2);

  if (args.length < 2) {
    console.log('Uso: node consulta_placa.js <placa> <imagem_captcha> [serie] [nome]');
    console.log('  placa:          Placa a consultar (ex: ABC1234 ou ABC1D23)');
    console.log('  imagem_captcha: Caminho da imagem BMP do captcha');
    console.log('  serie:          Série (opcional, padrão: P1400)');
    console.log('  nome:           Nome (opcional, padrão: LUCAS)');
    process.exit(1);
  }

  const placa = args[0];
  const imagePath = args[1];

  if (args[2]) CONFIG.serie = args[2];
  if (args[3]) CONFIG.nome = args[3];

  try {
    // Passo 1: Decodificar captcha
    const captcha = await decodificarCaptcha(imagePath);

    // Passo 2: Consultar placa
    const resultado = await consultarPlaca(placa, captcha);

    console.log('\n=== RESULTADO ===');
    console.log(resultado);
    console.log('==================\n');

    // Retorna o resultado como JSON
    console.log(JSON.stringify({ placa, resultado }, null, 2));
  } catch (error) {
    console.error('Erro:', error.message);
    process.exit(1);
  }
}

// Executa se chamado diretamente
if (require.main === module) {
  main();
}

module.exports = { decodificarCaptcha, consultarPlaca };
