import { createWorker } from 'tesseract.js';

let sharedWorker = null;
let sharedWorkerLang = null;
let sharedWorkerInitPromise = null;

function normalizeText(text) {
  return (text || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ')
    .trim()
    .toUpperCase();
}

function normalizeKeyword(value) {
  return normalizeText(String(value || ''));
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

function detectErrorDetails(text, customKeywords = []) {
  const known = [
    'FICHA CADASTRAL JA EXISTENTE',
    'FICHA CADASTRAL JA EXISTE',
    'FICHA JA EXISTENTE',
    'FICHA JA EXISTE',
    'ERRO',
    'NAO EXISTE',
    'INDEFERIDO',
    'NEGADO',
    ...(Array.isArray(customKeywords) ? customKeywords : []),
  ]
    .map((item) => String(item || '').trim())
    .filter(Boolean);

  for (const keyword of known) {
    const normalizedKeyword = normalizeKeyword(keyword);
    if (!normalizedKeyword) continue;
    if (text.includes(normalizedKeyword)) {
      return {
        message: keyword,
        code: null,
        reason: 'keyword',
      };
    }
  }

  const codeMatch = text.match(/\b[A-Z0-9]{3,6}-\d{2,4}\b/);
  const hasFicha = /FICHA/.test(text);
  const hasCadastral = /CADASTRAL/.test(text);
  const hasExist = /(EXISTE|EXISTENTE|JA EXIST|J A EXIST|JAEXIST)/.test(text);
  const hasDenied = /(INDEFERID|NEGAD|REPROVAD|NAO AUTORIZ|NAO PERMIT)/.test(text);
  const hasErrorWord = /\bERRO\b/.test(text);
  const hasCancelar = /\bCANCELAR\b/.test(text);

  if (hasFicha && hasCadastral && hasExist) {
    return {
      message: 'FICHA CADASTRAL JA EXISTENTE',
      code: codeMatch?.[0] || null,
      reason: 'ficha_exists_pattern',
    };
  }

  if (codeMatch && (hasFicha || hasCadastral || hasExist || hasDenied || hasErrorWord || hasCancelar)) {
    return {
      message: hasDenied ? 'NEGADO' : (hasErrorWord ? 'ERRO' : 'ERRO DE MODAL'),
      code: codeMatch[0],
      reason: 'modal_code_pattern',
    };
  }

  return null;
}

function detectTransient(text, customKeywords = []) {
  const defaults = [
    'NAO ESTA RESPONDENDO',
    'NÃO ESTÁ RESPONDENDO',
    'NAO ESTÁ RESPONDENDO',
    'NÃO ESTA RESPONDENDO',
    'NOT RESPONDING',
    'APLICATIVO NAO ESTA RESPONDENDO',
    'PROGRAMA NAO ESTA RESPONDENDO',
    'SEM RESPOSTA',
  ];

  const merged = [...defaults, ...(Array.isArray(customKeywords) ? customKeywords : [])]
    .map((item) => String(item || '').trim())
    .filter(Boolean);

  for (const keyword of merged) {
    const normalizedKeyword = normalizeKeyword(keyword);
    if (!normalizedKeyword) continue;
    if (text.includes(normalizedKeyword)) {
      return keyword;
    }
  }

  return null;
}

async function getSharedWorker(lang, logger = null) {
  const normalizedLang = String(lang || 'por').trim() || 'por';

  if (sharedWorker && sharedWorkerLang === normalizedLang) {
    return sharedWorker;
  }

  if (sharedWorkerInitPromise && sharedWorkerLang === normalizedLang) {
    return sharedWorkerInitPromise;
  }

  // Se trocar idioma, reinicia o worker compartilhado.
  if (sharedWorker && sharedWorkerLang !== normalizedLang) {
    try {
      await sharedWorker.terminate();
    } catch {
      // ignore
    }
    sharedWorker = null;
  }

  sharedWorkerLang = normalizedLang;
  sharedWorkerInitPromise = (async () => {
    logger?.info?.('ocr.worker.init', { lang: normalizedLang });
    const worker = await createWorker(normalizedLang);
    sharedWorker = worker;
    logger?.info?.('ocr.worker.ready', { lang: normalizedLang });
    return worker;
  })();

  try {
    return await sharedWorkerInitPromise;
  } finally {
    sharedWorkerInitPromise = null;
  }
}

export async function analyzeScreenshot(imagePath, opts = {}) {
  const lang = opts.lang || 'por';
  const logger = opts.logger || null;
  const transientKeywords = Array.isArray(opts.transientKeywords) ? opts.transientKeywords : [];
  const errorKeywords = Array.isArray(opts.errorKeywords) ? opts.errorKeywords : [];
  logger?.info('ocr.start', { imagePath, lang });
  const worker = await getSharedWorker(lang, logger);

  const { data } = await worker.recognize(imagePath);
  const rawText = data?.text || '';
  const normalized = normalizeText(rawText);

  const plates = extractPlates(normalized);
  const errorDetails = detectErrorDetails(normalized, errorKeywords);
  const errorMessage = errorDetails?.message || null;
  const errorCode = errorDetails?.code || null;
  const errorReason = errorDetails?.reason || null;
  const transientMessage = detectTransient(normalized, transientKeywords);

  const result = {
    rawText,
    normalizedText: normalized,
    plates,
    errorMessage,
    errorCode,
    errorReason,
    transientMessage,
  };
  logger?.info('ocr.done', {
    imagePath,
    platesCount: plates.length,
    errorMessage: errorMessage || null,
    errorCode,
    errorReason,
    transientMessage: transientMessage || null,
  });
  return result;
}

export async function warmupOcrWorker(opts = {}) {
  const lang = opts.lang || 'por';
  const logger = opts.logger || null;
  await getSharedWorker(lang, logger);
}
