import { createWorker } from 'tesseract.js';

function normalizeText(text) {
  return (text || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ')
    .trim()
    .toUpperCase();
}

function extractPlates(text) {
  const plates = new Set();

  const patterns = [
    /[A-Z]{3}-\d{4}/g,           // ABC-1234
    /[A-Z]{3}-\d[A-Z]\d{2}/g,     // UFY-9A48 (Mercosul com hífen)
    /[A-Z]{3}\d[A-Z]\d{2}/g,      // UFY9A48 (Mercosul sem hífen)
  ];

  for (const rx of patterns) {
    let m;
    while ((m = rx.exec(text))) {
      plates.add(m[0]);
    }
  }

  return Array.from(plates);
}

function detectError(text) {
  const known = [
    'FICHA CADASTRAL JA EXISTENTE',
    'FICHA CADASTRAL JÁ EXISTENTE',
    'FICHA CADASTRAL JA EXISTE',
    'FICHA CADASTRAL JÁ EXISTE',
    'ERRO',
    'NAO EXISTE',
    'NÃO EXISTE',
    'INDEFERIDO',
    'NEGADO',
  ];

  for (const k of known) {
    if (text.includes(k.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase())) {
      return k;
    }
  }

  return null;
}

export async function analyzeScreenshot(imagePath, opts = {}) {
  const lang = opts.lang || 'por';
  const worker = await createWorker(lang);

  try {
    const { data } = await worker.recognize(imagePath);
    const rawText = data?.text || '';
    const normalized = normalizeText(rawText);

    const plates = extractPlates(normalized);
    const errorMessage = detectError(normalized);

    return {
      rawText,
      normalizedText: normalized,
      plates,
      errorMessage,
    };
  } finally {
    await worker.terminate();
  }
}

