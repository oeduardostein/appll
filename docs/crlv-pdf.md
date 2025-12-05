# Como o aplicativo gera o PDF do CRLV-e

Este fluxo não renderiza o PDF localmente; ele faz proxy da emissão no Detran/SP.

- **Endpoint**: `GET /api/emissao-crlv` → `backend/app/Http/Controllers/Api/ImpressaoCrlvController.php`.
- **Entradas** (query string): `placa`, `renavam`, `captchaResponse`, `opcaoPesquisa` (1=CPF, 2=CNPJ) e, conforme a opção, `cpf` ou `cnpj`.
- **Token de sessão**: lê `admin_settings.id=1` (`value`) para enviar como cookie `JSESSIONID`.
- **Headers/Cookies**: monta headers de navegador e cookies (`naoExibirPublic`, `dataUsuarPublic`, `JSESSIONID`) exigidos pelo site `https://www.e-crvsp.sp.gov.br`.
- **Passo 1 – validação**: faz POST `method=pesquisar` para `/gever/GVR/emissao/impressaoCrlv.do`. Converte HTML ISO-8859-1 se necessário e procura `errors[errors.length] = "mensagem"` no corpo. Se achar, responde 422 com a mensagem.
- **Passo 2 – obtenção do PDF**: se não houver erros, faz GET no mesmo endpoint com `method=openPdf` usando os mesmos cookies. Valida se `Content-Type` contém `pdf`; caso contrário, devolve erro 502.
- **Resposta final**: retorna o corpo do PDF diretamente (HTTP 200) com headers:
  - `Content-Type: application/pdf`
  - `Content-Disposition: inline; filename="CRLV-<PLACA>-<timestamp>.pdf"`
  - `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`

> Observação: existe um template `backend/resources/views/pdf/crlv.blade.php`, mas este fluxo não o usa; ele apenas encaminha o PDF recebido do serviço do Detran/SP.
