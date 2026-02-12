#!/usr/bin/env node
import fs from "node:fs/promises";

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

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

async function main() {
  const args = parseArgs(process.argv.slice(2));

  const inputPath = args.input;
  if (!inputPath) {
    throw new Error("Use --input arquivo.json");
  }

  const baseUrl = (args.baseUrl ?? "http://127.0.0.1:8000").replace(/\/$/, "");
  const endpoint = `${baseUrl}/api/public/placas-0km/consultar`;

  const apiKey = args.apiKey ?? process.env.PUBLIC_PLACAS0KM_API_KEY ?? "";
  const delayMs = Number(args.delayMs ?? 500);
  const debug = Boolean(args.debug);

  const raw = await fs.readFile(inputPath, "utf8");
  const payload = JSON.parse(raw);
  if (!Array.isArray(payload)) {
    throw new Error("O arquivo JSON deve ser um array de itens.");
  }

  const results = [];

  for (let idx = 0; idx < payload.length; idx += 1) {
    const item = payload[idx] ?? {};
    const cpf = String(item.cpf_cgc ?? item.cpf ?? "").trim();
    const chassi = String(item.chassi ?? "").trim();
    const nome = String(item.nome ?? "").trim();
    const numeros = item.numeros != null ? String(item.numeros).trim() : undefined;

    if (!cpf || !chassi) {
      results.push({
        index: idx,
        success: false,
        error: "Item inválido: informe cpf_cgc e chassi.",
      });
      continue;
    }

    const body = {
      cpf_cgc: cpf,
      chassi,
      nome,
      ...(numeros ? { numeros } : {}),
      ...(debug ? { debug: true } : {}),
    };

    const headers = {
      "Content-Type": "application/json",
      Accept: "application/json",
    };
    if (apiKey) headers["X-Public-Api-Key"] = apiKey;

    process.stdout.write(`[${idx + 1}/${payload.length}] consultando...\n`);

    let response;
    try {
      response = await fetch(endpoint, {
        method: "POST",
        headers,
        body: JSON.stringify(body),
      });
    } catch (e) {
      results.push({
        index: idx,
        success: false,
        error: `Falha de rede: ${String(e?.message ?? e)}`,
      });
      await sleep(delayMs);
      continue;
    }

    const text = await response.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch {
      json = { raw: text };
    }

    results.push({
      index: idx,
      http_status: response.status,
      response: json,
    });

    await sleep(delayMs);
  }

  const outPath = args.output ?? "placas_0km_resultados.json";
  await fs.writeFile(outPath, JSON.stringify(results, null, 2), "utf8");
  process.stdout.write(`OK: resultados em ${outPath}\n`);
}

main().catch((err) => {
  console.error(String(err?.stack ?? err));
  process.exitCode = 1;
});

