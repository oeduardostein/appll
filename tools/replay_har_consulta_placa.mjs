#!/usr/bin/env node
import fs from "node:fs/promises";

const SENSITIVE_HEADERS = new Set([
  "authorization",
  "cookie",
  "set-cookie",
  "proxy-authorization",
]);

function parseArgs(argv) {
  const args = {};
  for (let i = 0; i < argv.length; i += 1) {
    const token = argv[i];
    if (!token.startsWith("--")) continue;
    const key = token.slice(2);
    const next = argv[i + 1];
    if (!next || next.startsWith("--")) {
      args[key] = true;
    } else {
      args[key] = next;
      i += 1;
    }
  }
  return args;
}

function harHeadersToObject(harHeaders) {
  const out = {};
  for (const h of harHeaders ?? []) {
    if (!h?.name) continue;
    out[h.name] = h.value ?? "";
  }
  return out;
}

function stripHopByHop(headers) {
  const hopByHop = new Set([
    "connection",
    "host",
    "content-length",
    "accept-encoding",
    "proxy-connection",
    "transfer-encoding",
    "upgrade",
  ]);

  const out = {};
  for (const [k, v] of Object.entries(headers)) {
    if (hopByHop.has(k.toLowerCase())) continue;
    out[k] = v;
  }
  return out;
}

function redactHeaders(headers, showSecrets) {
  if (showSecrets) return { ...headers };
  const out = {};
  for (const [k, v] of Object.entries(headers)) {
    if (SENSITIVE_HEADERS.has(k.toLowerCase()) && v) out[k] = "[REDACTED]";
    else out[k] = v;
  }
  return out;
}

function decodeHarResponseBody(entry) {
  const content = entry?.response?.content ?? {};
  const text = content.text;
  if (!text) return null;
  if (content.encoding === "base64") {
    return Buffer.from(text, "base64").toString("utf8");
  }
  return text;
}

function findEntry(entries, entryIndex, urlRegex) {
  if (entryIndex !== undefined) {
    const idx = Number(entryIndex);
    if (!Number.isFinite(idx) || idx < 0 || idx >= entries.length) {
      throw new Error(`--entry-index fora do range: 0..${entries.length - 1}`);
    }
    return { idx, entry: entries[idx] };
  }

  const pattern = new RegExp(urlRegex ?? "ChkRetorno2HTML", "i");
  for (let idx = 0; idx < entries.length; idx += 1) {
    const url = entries[idx]?.request?.url ?? "";
    if (pattern.test(url)) return { idx, entry: entries[idx] };
  }

  throw new Error("Nenhuma entry encontrada (tente --url-regex ou --entry-index).");
}

function bytesToString(bytes) {
  return Buffer.from(bytes).toString("utf8");
}

function stringToBytesArray(str) {
  return Array.from(Buffer.from(str, "utf8"));
}

function replaceParamValue(source, key, value) {
  // param0 inclui uma URL com querystring, então fazemos um replace simples.
  // Ex.: chassi=XXXXX  / captchaResponse=YYYYY
  const re = new RegExp(`(${key}=)([^&|]*)`, "g");
  if (!re.test(source)) return source;
  return source.replace(re, `$1${value}`);
}

function tryJson(text) {
  try {
    return JSON.parse(text);
  } catch {
    return null;
  }
}

async function main() {
  const args = parseArgs(process.argv.slice(2));
  const harPath = args.har ?? "e-system/consulta_placa.har";
  const showSecrets = Boolean(args["show-secrets"]);
  const replay = Boolean(args.replay);

  const raw = await fs.readFile(harPath, "utf8");
  const har = JSON.parse(raw);
  const entries = har?.log?.entries ?? [];
  if (!Array.isArray(entries) || entries.length === 0) {
    throw new Error("HAR sem entries.");
  }

  const { idx, entry } = findEntry(entries, args["entry-index"], args["url-regex"]);
  const req = entry.request ?? {};

  const method = (req.method ?? "GET").toUpperCase();
  const url = req.url ?? "";

  let headers = stripHopByHop(harHeadersToObject(req.headers));
  const authorization = args.authorization ?? process.env.AUTHORIZATION;
  if (authorization) headers.Authorization = authorization;

  const harRespBody = decodeHarResponseBody(entry);
  if (harRespBody != null) {
    const parsed = tryJson(harRespBody);
    if (parsed) {
      process.stdout.write(`HAR response (decoded JSON): ${JSON.stringify(parsed)}\n`);
    } else {
      process.stdout.write(`HAR response (decoded): ${harRespBody.slice(0, 5000)}\n`);
    }
  }

  process.stdout.write(`Entry: ${idx}\n`);
  process.stdout.write(`Request: ${method} ${url}\n`);
  process.stdout.write(
    `Headers: ${JSON.stringify(redactHeaders(headers, showSecrets), null, 2)}\n`,
  );

  if (!replay) return;

  if (method !== "POST") {
    throw new Error("Replay só implementado para POST neste script.");
  }

  let bodyText = req?.postData?.text ?? "";
  if (!bodyText) {
    throw new Error("Entry não tem postData.text.");
  }

  let payload = null;
  try {
    payload = JSON.parse(bodyText);
  } catch {
    payload = null;
  }

  if (payload && Array.isArray(payload._parameters)) {
    const params = payload._parameters;

    // Opcional: override do chassi e/ou captcha no param0 (string em bytes)
    if (args.chassi && Array.isArray(params[0])) {
      const p0 = bytesToString(params[0]);
      const updated = replaceParamValue(p0, "chassi", args.chassi);
      params[0] = stringToBytesArray(updated);
    }

    if (args.captcha && Array.isArray(params[0])) {
      const p0 = bytesToString(params[0]);
      const updated = replaceParamValue(p0, "captchaResponse", args.captcha);
      params[0] = stringToBytesArray(updated);
    }

    // param2 normalmente é "|<chassi/vin>"
    if (args.chassi && typeof params[2] === "string") {
      params[2] = `|${args.chassi}`;
    }

    bodyText = JSON.stringify(payload);
  }

  const contentType =
    headers["Content-Type"] ??
    headers["content-type"] ??
    req?.postData?.mimeType ??
    "text/plain;charset=UTF-8";

  headers["Content-Type"] = contentType;

  const resp = await fetch(url, {
    method,
    headers,
    body: bodyText,
  });

  const respText = await resp.text();
  process.stdout.write(`HTTP status: ${resp.status}\n`);
  process.stdout.write(`Content-Type: ${resp.headers.get("content-type") ?? ""}\n`);

  // Alguns endpoints retornam JSON base64 via "text" — tenta decodificar se for o caso
  const parsedDirect = tryJson(respText);
  if (parsedDirect) {
    process.stdout.write(`Replay response JSON: ${JSON.stringify(parsedDirect)}\n`);
    return;
  }

  // fallback: tenta base64 -> JSON
  try {
    const decoded = Buffer.from(respText, "base64").toString("utf8");
    const parsed = tryJson(decoded);
    if (parsed) {
      process.stdout.write(`Replay response (base64->JSON): ${JSON.stringify(parsed)}\n`);
      return;
    }
  } catch {
    // ignore
  }

  process.stdout.write(`Replay response (text): ${respText.slice(0, 5000)}\n`);
}

main().catch((err) => {
  console.error(String(err?.stack ?? err));
  process.exitCode = 1;
});

