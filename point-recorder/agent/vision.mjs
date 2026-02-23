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

function normalizeWordText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toUpperCase()
    .replace(/[^A-Z0-9-]/g, '')
    .trim();
}

const LETTER_FROM_DIGIT_MAP = Object.freeze({
  '0': 'O',
  '1': 'J',
  '2': 'Z',
  '4': 'A',
  '5': 'S',
  '6': 'G',
  '7': 'T',
  '8': 'B',
  '9': 'G',
});

const DIGIT_FROM_LETTER_MAP = Object.freeze({
  A: '4',
  O: '0',
  Q: '0',
  D: '0',
  I: '1',
  L: '1',
  J: '1',
  T: '1',
  Z: '2',
  S: '5',
  G: '6',
  B: '8',
});

function formatPlate(compact) {
  return `${compact.slice(0, 3)}-${compact.slice(3)}`;
}

function coerceLetter(char) {
  const upper = String(char || '').toUpperCase();
  if (!upper) return null;
  if (/[A-Z]/.test(upper)) return upper;
  return LETTER_FROM_DIGIT_MAP[upper] || null;
}

function coerceDigit(char) {
  const upper = String(char || '').toUpperCase();
  if (!upper) return null;
  if (/\d/.test(upper)) return upper;
  return DIGIT_FROM_LETTER_MAP[upper] || null;
}

function normalizeClassicPlate(compact) {
  if (compact.length !== 7) return null;
  let out = '';

  for (let i = 0; i < 3; i += 1) {
    const letter = coerceLetter(compact[i]);
    if (!letter) return null;
    out += letter;
  }
  for (let i = 3; i < 7; i += 1) {
    const digit = coerceDigit(compact[i]);
    if (!digit) return null;
    out += digit;
  }

  return /^[A-Z]{3}\d{4}$/.test(out) ? out : null;
}

function normalizeMercosulPlate(compact) {
  if (compact.length !== 7) return null;
  const p0 = coerceLetter(compact[0]);
  const p1 = coerceLetter(compact[1]);
  const p2 = coerceLetter(compact[2]);
  const p3 = coerceDigit(compact[3]);
  const p4 = coerceLetter(compact[4]);
  const p5 = coerceDigit(compact[5]);
  const p6 = coerceDigit(compact[6]);

  if (!p0 || !p1 || !p2 || !p3 || !p4 || !p5 || !p6) {
    return null;
  }

  const out = `${p0}${p1}${p2}${p3}${p4}${p5}${p6}`;
  return /^[A-Z]{3}\d[A-Z]\d{2}$/.test(out) ? out : null;
}

function normalizePlateCandidate(rawCandidate) {
  const compact = String(rawCandidate || '')
    .replace(/[^A-Z0-9]/gi, '')
    .toUpperCase();

  if (compact.length !== 7) return null;

  if (/^[A-Z]{3}\d[A-Z]\d{2}$/.test(compact)) {
    return formatPlate(compact);
  }
  if (/^[A-Z]{3}\d{4}$/.test(compact)) {
    return formatPlate(compact);
  }

  const mercosul = normalizeMercosulPlate(compact);
  if (mercosul) {
    return formatPlate(mercosul);
  }

  const classic = normalizeClassicPlate(compact);
  if (classic) {
    return formatPlate(classic);
  }

  return null;
}

function extractPlates(text, collector = null) {
  const normalized = normalizeText(String(text || ''));
  const seen = new Set();
  const out = [];
  const addPlate = (value, meta = null) => {
    if (!value || seen.has(value)) return;
    seen.add(value);
    out.push(value);
    if (collector && typeof collector.bump === 'function') {
      collector.bump(value, meta);
    }
  };

  // Passo 1: extrai padrões estritos com/sem hífen.
  const strictPatterns = [
    /\b[A-Z]{3}[-\s]?\d{4}\b/g,
    /\b[A-Z]{3}[-\s]?\d[A-Z]\d{2}\b/g,
  ];

  for (const rx of strictPatterns) {
    let match;
    while ((match = rx.exec(normalized)) !== null) {
      const normalizedPlate = normalizePlateCandidate(match[0]);
      addPlate(normalizedPlate, { source: 'text_strict', raw: match[0], weight: 3 });
    }
  }

  // Passo 2: fallback tolerante para OCR confundir letra/número.
  const tolerantPattern = /\b[A-Z0-9]{3}[-\s]?[A-Z0-9]{4}\b/g;
  let match;
  while ((match = tolerantPattern.exec(normalized)) !== null) {
    const raw = String(match[0] || '');
    const compact = raw.replace(/[^A-Z0-9]/g, '');
    if (compact.length !== 7) continue;
    const digitCount = (compact.match(/\d/g) || []).length;
    const hasSeparator = /[-\s]/.test(raw);
    if (!hasSeparator) {
      if (!/^[A-Z]/.test(compact)) continue;
      const first3 = compact.slice(0, 3);
      const first3Letters = (first3.match(/[A-Z]/g) || []).length;
      if (first3Letters < 2) continue;
    }
    if (!hasSeparator && digitCount < 2) continue;
    if (hasSeparator && digitCount < 1) continue;
    const normalizedPlate = normalizePlateCandidate(raw);
    addPlate(normalizedPlate, { source: 'text_tolerant', raw, weight: 1 });
  }

  // Passo 3: captura placas "coladas" (sem depender de borda de palavra).
  const gluedHyphenPattern = /[A-Z0-9]{3}-[A-Z0-9]{4}/g;
  while ((match = gluedHyphenPattern.exec(normalized)) !== null) {
    const chunk = String(match[0] || '');
    const normalizedPlate = normalizePlateCandidate(chunk);
    addPlate(normalizedPlate, { source: 'glued_hyphen', raw: chunk, weight: 2 });
  }

  return out;
}

function extractPlatesFromWords(words, collector = null) {
  if (!Array.isArray(words) || words.length === 0) return [];

  const seen = new Set();
  const out = [];
  const addPlate = (plate, meta = null) => {
    if (!plate || seen.has(plate)) return;
    seen.add(plate);
    out.push(plate);
    if (collector && typeof collector.bump === 'function') {
      collector.bump(plate, meta);
    }
  };

  const normalizedWords = words
    .map((word, idx) => {
      const rawText = String(word?.text || '');
      const text = normalizeWordText(rawText);
      if (!text) return null;
      const bbox = word?.bbox || {};
      const x0 = Number.isFinite(Number(bbox.x0)) ? Number(bbox.x0) : 0;
      const x1 = Number.isFinite(Number(bbox.x1)) ? Number(bbox.x1) : x0;
      const y0 = Number.isFinite(Number(bbox.y0)) ? Number(bbox.y0) : 0;
      const y1 = Number.isFinite(Number(bbox.y1)) ? Number(bbox.y1) : y0;
      const yCenter = (y0 + y1) / 2;
      return { idx, rawText, text, x0, x1, y0, y1, yCenter };
    })
    .filter(Boolean);

  // 1) Cada palavra isolada.
  for (const word of normalizedWords) {
    const candidate = normalizePlateCandidate(word.text);
    addPlate(candidate, { source: 'word_single', raw: word.text, weight: 2 });
  }

  // 2) Junta palavras vizinhas da mesma linha (ex.: "FWL-" + "2J11").
  const lineTolerance = 14;
  const sorted = [...normalizedWords].sort((a, b) => {
    if (Math.abs(a.yCenter - b.yCenter) <= lineTolerance) {
      return a.x0 - b.x0;
    }
    return a.yCenter - b.yCenter;
  });

  const lines = [];
  for (const word of sorted) {
    const current = lines[lines.length - 1];
    if (!current || Math.abs(current.yCenter - word.yCenter) > lineTolerance) {
      lines.push({ yCenter: word.yCenter, words: [word] });
      continue;
    }
    const nextCount = current.words.length + 1;
    current.yCenter = ((current.yCenter * current.words.length) + word.yCenter) / nextCount;
    current.words.push(word);
  }

  for (const line of lines) {
    const list = line.words.sort((a, b) => a.x0 - b.x0);
    for (let i = 0; i < list.length; i += 1) {
      const w1 = list[i];
      const w2 = list[i + 1];
      if (!w2) continue;
      const gap = w2.x0 - w1.x1;
      if (gap > 24) continue;

      const merged = `${w1.text}${w2.text}`;
      const candidate = normalizePlateCandidate(merged);
      addPlate(candidate, { source: 'word_pair', raw: merged, weight: 3 });
    }
  }

  return out;
}

function toCompactPlate(plate) {
  return String(plate || '').replace(/[^A-Z0-9]/g, '').toUpperCase();
}

function isConfusablePair(a, b) {
  if (a === b) return true;
  const pairs = new Set([
    '0:O', 'O:0', '0:Q', 'Q:0', '0:D', 'D:0',
    '1:I', 'I:1', '1:L', 'L:1', '1:J', 'J:1', '1:T', 'T:1',
    '2:Z', 'Z:2',
    '4:A', 'A:4',
    '5:S', 'S:5', '5:8', '8:5',
    '6:G', 'G:6',
    '8:B', 'B:8', '8:3', '3:8',
  ]);
  return pairs.has(`${a}:${b}`);
}

function areLikelyVariantPlates(a, b) {
  const ca = toCompactPlate(a);
  const cb = toCompactPlate(b);
  if (ca.length !== 7 || cb.length !== 7) return false;

  let hardDiffs = 0;
  let anyDiff = false;
  for (let i = 0; i < 7; i += 1) {
    if (ca[i] === cb[i]) continue;
    anyDiff = true;
    if (!isConfusablePair(ca[i], cb[i])) {
      hardDiffs += 1;
    }
  }

  if (!anyDiff) return true;
  return hardDiffs <= 1;
}

function createPlateCollector() {
  const stats = new Map();
  const seenOrder = [];

  const bump = (plate, meta = null) => {
    if (!plate) return;
    if (!stats.has(plate)) {
      stats.set(plate, {
        plate,
        score: 0,
        hits: 0,
        strictHits: 0,
        passSet: new Set(),
        sourceSet: new Set(),
      });
      seenOrder.push(plate);
    }

    const item = stats.get(plate);
    const weight = Number(meta?.weight);
    const safeWeight = Number.isFinite(weight) && weight > 0 ? weight : 1;
    item.score += safeWeight;
    item.hits += 1;
    if (String(meta?.source || '').includes('strict')) {
      item.strictHits += 1;
    }
    if (meta?.pass) {
      item.passSet.add(String(meta.pass));
    }
    if (meta?.source) {
      item.sourceSet.add(String(meta.source));
    }
  };

  const values = (opts = {}) => {
    const maxPlatesRaw = Number(opts?.maxPlates);
    const maxPlates = Number.isFinite(maxPlatesRaw) && maxPlatesRaw > 0 ? Math.floor(maxPlatesRaw) : 18;

    const ranked = seenOrder
      .map((plate) => {
        const item = stats.get(plate);
        return {
          plate,
          score: item?.score || 0,
          hits: item?.hits || 0,
          strictHits: item?.strictHits || 0,
          passCount: item?.passSet?.size || 0,
          sourceCount: item?.sourceSet?.size || 0,
        };
      })
      .sort((a, b) => {
        if (b.passCount !== a.passCount) return b.passCount - a.passCount;
        if (b.strictHits !== a.strictHits) return b.strictHits - a.strictHits;
        if (b.score !== a.score) return b.score - a.score;
        if (b.hits !== a.hits) return b.hits - a.hits;
        return a.plate.localeCompare(b.plate);
      });

    const deduped = [];
    const highSupport = ranked.filter((item) => item.passCount >= 2);
    const phases = [highSupport, ranked];

    for (const phase of phases) {
      for (const candidate of phase) {
        if (deduped.length >= maxPlates) break;
        const variantOfExisting = deduped.some((chosen) => areLikelyVariantPlates(chosen.plate, candidate.plate));
        if (variantOfExisting) continue;
        deduped.push(candidate);
      }
      if (deduped.length >= maxPlates) break;
    }

    return deduped.slice(0, maxPlates).map((item) => item.plate);
  };

  return { bump, values };
}

async function recognizeWithPasses(worker, imagePath, logger = null) {
  const passes = [
    {
      name: 'default',
      parameters: null,
    },
    {
      name: 'plate_block',
      parameters: {
        tessedit_pageseg_mode: '6',
        preserve_interword_spaces: '1',
        tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-',
      },
    },
    {
      name: 'plate_sparse',
      parameters: {
        tessedit_pageseg_mode: '11',
        preserve_interword_spaces: '1',
        tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-',
      },
    },
  ];

  const results = [];
  for (const pass of passes) {
    if (pass.parameters) {
      await worker.setParameters(pass.parameters);
    }
    const { data } = await worker.recognize(imagePath);
    results.push({ name: pass.name, data });
  }

  logger?.info?.('ocr.passes.done', {
    imagePath,
    passes: results.map((item) => item.name),
  });
  return results;
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

  const passResults = await recognizeWithPasses(worker, imagePath, logger);
  const primaryData = passResults[0]?.data || {};
  const rawText = primaryData?.text || '';

  const collector = createPlateCollector();
  const normalizedParts = [];
  for (const passResult of passResults) {
    const passName = String(passResult?.name || 'unknown');
    const passData = passResult?.data || {};
    const passRawText = passData?.text || '';
    const passNormalizedText = normalizeText(passRawText);
    if (passNormalizedText) {
      normalizedParts.push(passNormalizedText);
    }
    extractPlates(passNormalizedText, {
      bump: (plate, meta = null) => collector.bump(plate, { ...(meta || {}), pass: passName }),
    });
    extractPlatesFromWords(passData?.words, {
      bump: (plate, meta = null) => collector.bump(plate, { ...(meta || {}), pass: passName }),
    });
  }
  const normalized = normalizedParts.join(' ').trim();
  const plates = collector.values({ maxPlates: opts.maxPlates || 18 });

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
