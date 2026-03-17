# Click Automation

Automação de cliques para Windows - Baseado nos scripts `point-recorder` que já funcionam!

## Instalação

Não precisa de `npm install`! Usa apenas JavaScript puro + PowerShell.

## Como usar

### 1. Capturar cliques (sem abrir navegador)

```bash
npm run capture
```

### 2. Capturar cliques (abrindo Chrome com URL)

```bash
npm run capture:ecrv
```

Isso abrirá o Chrome em `https://www.e-crvsp.sp.gov.br/`

Ou com qualquer URL:

```bash
npm run capture -- --url "https://seu-site.com"
```

### 3. Executar automação

```bash
npm run run
```

## Opções de linha de comando

```
--out, -o <arquivo>    Arquivo de saída (default: clicks.json)
--url, -u <url>        URL para abrir no Chrome automaticamente
--card-ms <ms>         Tempo para mostrar card visual (default: 1200)
--poll-ms <ms>         Intervalo de poll do mouse (default: 20)
--help, -h             Mostra ajuda
```

## Exemplos

```bash
# Capturar cliques com E-CRV SP
npm run capture -- -u "https://ecrv.sp.gov.br"

# Capturar com saída em outro arquivo
npm run capture -- -o pontos-login.json -u "https://ecrv.sp.gov.br"

# Executar automação
npm run run
```

## Como capturar

1. Execute `npm run capture` (ou `npm run capture:ecrv` para abrir o E-CRV)
2. O Chrome abrirá com a URL (se fornecida)
3. Uma janela "Point Picker" abrirá
4. **Clique com o botão ESQUERDO** onde você quer capturar
5. Cada clique será mostrado na janela
6. Pressione **ESC** para salvar e sair

## Estrutura do arquivo clicks.json

```json
[
  {
    "index": 1,
    "x": 100,
    "y": 200,
    "ts": "2026-03-13T12:00:00.000Z"
  }
]
```

## Scripts PowerShell incluídos

- `win-pick-points.ps1` - Captura cliques do mouse (abre Chrome se URL fornecida)
- `win-replay-simple.ps1` - Executa os cliques capturados

## Requisitos

- **Windows 7+** com PowerShell
- **Google Chrome** instalado em `C:\Program Files\Google\Chrome\Application\chrome.exe`
- **Node.js** v14 ou superior
