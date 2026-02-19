# Gravador de pontos (X/Y) + teclado

Script em Node.js para capturar **cliques do mouse** e **digitação do teclado** no sistema e salvar os eventos em um arquivo `.json` (para replay depois).

## Requisitos

- Node.js 18+ (recomendado)
- Ferramentas de build para módulos nativos (o pacote usa `node-gyp`):
  - **Linux:** `build-essential`, `python3`, `make`, `g++`
  - **Windows:** Visual Studio Build Tools
  - **macOS:** Xcode Command Line Tools

## Instalação

```bash
cd point-recorder
npm install
```

## Uso

Grava em JSON no arquivo padrão `point-recorder/recordings/session.json`:

```bash
npm start
```

Grava um **template** com slots (CPF/NOME/CHASSI) + ponto de screenshot:

```bash
npm run record:template
```

Calibrar visualmente os pontos de um template (Windows, UI topmost arrastável):

```bash
npm run calibrate:template
```

Se `recordings/template.json` nao existir, o script tenta detectar automaticamente um template em `recordings/*.json`.
O arquivo de saida padrao vira `<template>.calibrated.json` no mesmo diretorio do template escolhido.
Durante a calibracao: `Enter` confirma, `S` mantem, `Esc` cancela, `F6/F7/F8/F9` marcam `cpf_cgc/nome/chassi/senha`.
Obs: a calibracao agora roda em modo rapido por acao (ex.: click completo), nao em todos os pontos brutos.

Escolher arquivo de saída e formato:

```bash
node record.js --out recordings/minha-sessao.json --format json
node record.js --out recordings/minha-sessao.jsonl --format jsonl
node agent/calibrate.mjs --template recordings/meu-template.json --out recordings/meu-template.calibrated.json
```

## Como parar

- `Ctrl+C` para encerrar.

## Observações

- Alguns sistemas podem exigir permissões extras para capturar eventos globais (especialmente macOS).
- `--format jsonl` é **append** (não sobrescreve). Apague o arquivo se quiser começar do zero.
- `--format json` precisa de arquivo vazio (ele grava um único array JSON válido).
