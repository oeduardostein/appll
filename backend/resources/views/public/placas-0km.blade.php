<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Fila • Placas 0KM</title>
    <style>
        :root {
            --bg: #0b1220;
            --card: rgba(255, 255, 255, 0.08);
            --card-strong: rgba(255, 255, 255, 0.12);
            --text: rgba(255, 255, 255, 0.92);
            --muted: rgba(255, 255, 255, 0.68);
            --border: rgba(255, 255, 255, 0.14);
            --brand: #3b82f6;
            --brand-2: #60a5fa;
            --ok: #22c55e;
            --warn: #f59e0b;
            --err: #ef4444;
        }

        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji",
                "Segoe UI Emoji";
            background: radial-gradient(1200px 600px at 20% 10%, rgba(96, 165, 250, 0.22), transparent 60%),
                radial-gradient(1200px 600px at 90% 20%, rgba(34, 197, 94, 0.16), transparent 60%),
                var(--bg);
            color: var(--text);
        }

        .container {
            max-width: 1080px;
            margin: 0 auto;
            padding: 28px 18px 64px;
        }

        .header {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 22px;
        }

        .header h1 {
            font-size: 22px;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .header p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .grid {
            display: grid;
            gap: 18px;
        }

        @media (min-width: 980px) {
            .grid {
                grid-template-columns: 1fr 1.1fr;
            }
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px;
            backdrop-filter: blur(8px);
        }

        .card h2 {
            font-size: 16px;
            margin: 0 0 10px;
        }

        .field {
            display: grid;
            gap: 6px;
            margin-bottom: 12px;
        }

        .field label {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
        }

        .field input,
        .field textarea {
            border: 1px solid var(--border);
            background: rgba(0, 0, 0, 0.18);
            color: var(--text);
            border-radius: 12px;
            padding: 10px 12px;
            outline: none;
            font-size: 13px;
        }

        .field input:focus,
        .field textarea:focus {
            border-color: rgba(96, 165, 250, 0.6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
        }

        textarea {
            min-height: 240px;
            resize: vertical;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New",
                monospace;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            border-radius: 12px;
            background: var(--brand);
            color: white;
            padding: 10px 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn:hover {
            background: var(--brand-2);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn.warn {
            background: #dc2626;
        }

        .btn.warn:hover {
            background: #ef4444;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.06);
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            color: var(--muted);
        }

        .bar {
            height: 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .bar > div {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, rgba(59, 130, 246, 1), rgba(34, 197, 94, 1));
            transition: width 200ms ease;
        }

        .statusRow {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .muted {
            color: var(--muted);
            font-size: 13px;
        }

        .errorBox {
            display: none;
            margin-top: 10px;
            background: rgba(239, 68, 68, 0.14);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: rgba(255, 255, 255, 0.92);
            padding: 10px 12px;
            border-radius: 12px;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 10px 8px;
            vertical-align: top;
            font-size: 12px;
        }

        th {
            text-align: left;
            color: rgba(255, 255, 255, 0.72);
            font-weight: 800;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 8px;
            font-weight: 800;
            font-size: 11px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.06);
        }

        .tag.ok { border-color: rgba(34, 197, 94, 0.35); background: rgba(34, 197, 94, 0.14); }
        .tag.err { border-color: rgba(239, 68, 68, 0.35); background: rgba(239, 68, 68, 0.14); }
        .tag.run { border-color: rgba(245, 158, 11, 0.35); background: rgba(245, 158, 11, 0.14); }

        .smallMono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New",
                monospace;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.8);
            word-break: break-word;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Fila pública • Consulta de Placas 0KM</h1>
        <p>Enfileira várias consultas (CPF/CNPJ + Chassi) e executa 1 por vez. A página fica “carregando” até concluir.</p>
    </div>

    <div class="grid">
        <section class="card">
            <h2>Enviar lista</h2>

            <div class="field">
                <label for="apiKey">X-Public-Api-Key (opcional)</label>
                <input id="apiKey" type="password" placeholder="se configurado no servidor">
            </div>

            <div class="field" style="margin-top: 14px;">
                <label style="margin-bottom: 8px;">Enviar 1 item (mais simples)</label>
                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="field" style="margin: 0;">
                        <label for="cpfCgc">CPF/CNPJ</label>
                        <input id="cpfCgc" type="text" placeholder="somente números ou com pontuação">
                    </div>
                    <div class="field" style="margin: 0;">
                        <label for="nome">Nome</label>
                        <input id="nome" type="text" placeholder="opcional">
                    </div>
                    <div class="field" style="margin: 0;">
                        <label for="chassi">Chassi</label>
                        <input id="chassi" type="text" placeholder="ex: 94DFAAP16TB015294">
                    </div>
                    <div class="field" style="margin: 0;">
                        <label for="numeros">Complemento (numeros)</label>
                        <input id="numeros" type="text" placeholder="até 4 caracteres (opcional)">
                    </div>
                </div>
                <div class="actions" style="margin-top: 10px;">
                    <button class="btn" id="enqueueSingleBtn" type="button">Enfileirar 1 item</button>
                    <span class="pill">Dica: isso já salva no banco e entra na fila.</span>
                </div>
            </div>

            <div class="field">
                <label for="itemsJson">Itens (JSON array)</label>
                <textarea id="itemsJson" spellcheck="false" placeholder='[
  {"cpf_cgc":"07170716852","nome":"Fulano","chassi":"94DFAAP16TB015294"},
  {"cpf_cgc":"12345678901","nome":"Beltrano","chassi":"9C2JC4810BR016583","numeros":"1A23"}
]'></textarea>
            </div>

            <div class="actions">
                <button class="btn" id="enqueueBtn" type="button">Enfileirar</button>
                <button class="btn warn" id="resetRunnerBtn" type="button">Resetar runner (id 1 / req 10)</button>
                <span class="pill" id="hintPill">Dica: você pode enviar outra lista enquanto uma está rodando.</span>
            </div>
            <div class="errorBox" id="errorBox"></div>
        </section>

        <section class="card">
            <h2>Status</h2>
            <div class="muted" id="statusText">Nenhuma fila selecionada.</div>
            <div class="bar" style="margin-top: 12px;"><div id="progressFill"></div></div>
            <div class="statusRow">
                <span class="pill" id="batchPill">Batch: —</span>
                <span class="pill" id="runnerPill">Runner: —</span>
            </div>
            <div class="muted" id="currentText" style="margin-top: 10px;">Atual: —</div>
            <div class="muted" id="countsText" style="margin-top: 6px;">—</div>

            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>CPF/CNPJ</th>
                    <th>Nome</th>
                    <th>Chassi</th>
                    <th>Status</th>
                    <th>Placas</th>
                    <th>Erro</th>
                </tr>
                </thead>
                <tbody id="rowsBody">
                <tr><td colspan="7" class="muted">—</td></tr>
                </tbody>
            </table>
        </section>
    </div>

    <section class="card" style="margin-top: 18px;">
        <h2>Últimas filas</h2>
        <div class="muted" id="batchesText">Carregando…</div>
        <table>
            <thead>
            <tr>
                <th>Batch</th>
                <th>Status</th>
                <th>Total</th>
                <th>Processado</th>
                <th>OK</th>
                <th>Falhas</th>
            </tr>
            </thead>
            <tbody id="batchesBody">
            <tr><td colspan="6" class="muted">—</td></tr>
            </tbody>
        </table>
    </section>
</div>

<script>
    (function () {
        const initialBatchId = @json($batchId ?? null);

        const apiKeyEl = document.getElementById('apiKey');
        const cpfCgcEl = document.getElementById('cpfCgc');
        const nomeEl = document.getElementById('nome');
        const chassiEl = document.getElementById('chassi');
        const numerosEl = document.getElementById('numeros');
        const enqueueSingleBtn = document.getElementById('enqueueSingleBtn');
        const itemsJsonEl = document.getElementById('itemsJson');
        const enqueueBtn = document.getElementById('enqueueBtn');
        const resetRunnerBtn = document.getElementById('resetRunnerBtn');
        const errorBox = document.getElementById('errorBox');

        const statusText = document.getElementById('statusText');
        const batchPill = document.getElementById('batchPill');
        const runnerPill = document.getElementById('runnerPill');
        const currentText = document.getElementById('currentText');
        const countsText = document.getElementById('countsText');
        const progressFill = document.getElementById('progressFill');
        const rowsBody = document.getElementById('rowsBody');

        const batchesText = document.getElementById('batchesText');
        const batchesBody = document.getElementById('batchesBody');

        let pollingTimer = null;
        let currentBatchId = initialBatchId ? String(initialBatchId) : null;

        const STORAGE = {
            apiKey: 'public_placas0km_api_key',
            itemsDraft: 'public_placas0km_items_json_draft',
            cpfCgcDraft: 'public_placas0km_cpf_cgc_draft',
            nomeDraft: 'public_placas0km_nome_draft',
            chassiDraft: 'public_placas0km_chassi_draft',
            numerosDraft: 'public_placas0km_numeros_draft',
        };

        function getApiKey() {
            return (apiKeyEl.value || localStorage.getItem(STORAGE.apiKey) || '').trim();
        }

        function setApiKey(value) {
            const v = (value || '').trim();
            if (v) localStorage.setItem(STORAGE.apiKey, v);
        }

        function setItemsDraft(value) {
            localStorage.setItem(STORAGE.itemsDraft, String(value ?? ''));
        }

        function getItemsDraft() {
            return localStorage.getItem(STORAGE.itemsDraft) ?? '';
        }

        function clearItemsDraft() {
            localStorage.removeItem(STORAGE.itemsDraft);
        }

        function setDraft(key, value) {
            localStorage.setItem(key, String(value ?? ''));
        }

        function getDraft(key) {
            return localStorage.getItem(key) ?? '';
        }

        function showError(message) {
            errorBox.textContent = message;
            errorBox.style.display = 'block';
        }

        function clearError() {
            errorBox.textContent = '';
            errorBox.style.display = 'none';
        }

        function tag(status) {
            const s = String(status || '').toLowerCase();
            if (s === 'succeeded') return '<span class="tag ok">OK</span>';
            if (s === 'failed') return '<span class="tag err">FALHA</span>';
            if (s === 'running') return '<span class="tag run">RODANDO</span>';
            return '<span class="tag">PENDENTE</span>';
        }

        function extractPlates(responsePayload) {
            const plates = responsePayload?.data?.placas;
            if (!Array.isArray(plates)) return '';
            return plates.join(', ');
        }

        async function apiFetch(path, options) {
            const headers = options?.headers ? { ...options.headers } : {};
            const key = getApiKey();
            if (key) headers['X-Public-Api-Key'] = key;
            return fetch(path, { ...options, headers });
        }

        async function refreshBatches() {
            try {
                const resp = await apiFetch('/api/public/placas-0km/batches', { method: 'GET' });
                const json = await resp.json().catch(() => null);
                if (!resp.ok || !json?.success) throw new Error(json?.error || 'Falha ao carregar batches.');

                const list = json.data?.batches || [];
                batchesText.textContent = list.length ? '' : 'Nenhuma fila ainda.';
                batchesBody.innerHTML = '';
                for (const b of list) {
                    const id = b.id;
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><a href="/public/placas-0km/${id}" style="color: #93c5fd; text-decoration: none;">#${id}</a></td>
                        <td class="smallMono">${b.status}</td>
                        <td>${b.total}</td>
                        <td>${b.processed}</td>
                        <td>${b.succeeded}</td>
                        <td>${b.failed}</td>
                    `;
                    batchesBody.appendChild(row);
                }
            } catch (e) {
                batchesText.textContent = 'Falha ao carregar.';
            }
        }

        async function refreshStatus() {
            if (!currentBatchId) return;

            try {
                const resp = await apiFetch(`/api/public/placas-0km/batches/${currentBatchId}`, { method: 'GET' });
                const json = await resp.json().catch(() => null);
                if (!resp.ok || !json?.success) throw new Error(json?.error || 'Falha ao carregar status.');

                const batch = json.data?.batch;
                const runner = json.data?.runner;
                const current = json.data?.current;
                const requests = json.data?.requests || [];

                batchPill.textContent = `Batch: #${batch?.id ?? currentBatchId}`;
                runnerPill.textContent = `Runner: ${runner?.is_running ? 'rodando' : 'ocioso'}${runner?.current_request_id ? ' (#' + runner.current_request_id + ')' : ''}`;

                const total = Number(batch?.total || 0);
                const processed = Number(batch?.processed || 0);
                const succeeded = Number(batch?.succeeded || 0);
                const failed = Number(batch?.failed || 0);

                const pct = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 0;
                progressFill.style.width = pct + '%';
                countsText.textContent = `Status: ${batch?.status} • Total ${total} • Processado ${processed} • OK ${succeeded} • Falhas ${failed}`;

                if (current) {
                    currentText.textContent = `Atual: #${current.id} • ${current.cpf_cgc} • ${current.chassi}`;
                } else {
                    currentText.textContent = 'Atual: —';
                }

                statusText.textContent = batch?.status === 'completed'
                    ? 'Concluído.'
                    : 'Processando (a página atualiza automaticamente).';

                rowsBody.innerHTML = '';
                if (!requests.length) {
                    rowsBody.innerHTML = '<tr><td colspan="7" class="muted">Sem itens.</td></tr>';
                } else {
                    for (const r of requests) {
                        const row = document.createElement('tr');
                        const plates = extractPlates(r.response_payload);
                        row.innerHTML = `
                            <td class="smallMono">#${r.id}</td>
                            <td class="smallMono">${r.cpf_cgc}</td>
                            <td>${r.nome ?? ''}</td>
                            <td class="smallMono">${r.chassi}${r.numeros ? ' / ' + r.numeros : ''}</td>
                            <td>${tag(r.status)}</td>
                            <td class="smallMono">${plates}</td>
                            <td class="smallMono">${r.response_error ?? ''}</td>
                        `;
                        rowsBody.appendChild(row);
                    }
                }

                if (batch?.status === 'completed') {
                    stopPolling();
                    await refreshBatches();
                }
            } catch (e) {
                showError(String(e?.message || e));
            }
        }

        function startPolling() {
            stopPolling();
            pollingTimer = setInterval(refreshStatus, 2000);
            refreshStatus();
        }

        function stopPolling() {
            if (pollingTimer) clearInterval(pollingTimer);
            pollingTimer = null;
        }

        enqueueBtn.addEventListener('click', async () => {
            clearError();
            setApiKey(apiKeyEl.value);

            let parsed;
            try {
                parsed = JSON.parse(itemsJsonEl.value);
            } catch {
                showError('JSON inválido. Envie um array JSON de itens.');
                return;
            }

            if (!Array.isArray(parsed) || parsed.length === 0) {
                showError('Envie um array JSON com pelo menos 1 item.');
                return;
            }

            enqueueBtn.disabled = true;
            try {
                const resp = await apiFetch('/api/public/placas-0km/batches', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ items: parsed })
                });

                const json = await resp.json().catch(() => null);
                if (!resp.ok || !json?.success) {
                    throw new Error(json?.error || json?.message || `HTTP ${resp.status}`);
                }

                clearItemsDraft();
                const batchId = String(json.data.batch_id);
                window.location.href = `/public/placas-0km/${batchId}`;
            } catch (e) {
                showError(String(e?.message || e));
            } finally {
                enqueueBtn.disabled = false;
            }
        });

        enqueueSingleBtn.addEventListener('click', async () => {
            clearError();
            setApiKey(apiKeyEl.value);

            const item = {
                cpf_cgc: (cpfCgcEl.value || '').trim(),
                nome: (nomeEl.value || '').trim(),
                chassi: (chassiEl.value || '').trim(),
                numeros: (numerosEl.value || '').trim(),
            };

            enqueueSingleBtn.disabled = true;
            try {
                const resp = await apiFetch('/api/public/placas-0km/batches', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ items: [item] })
                });

                const json = await resp.json().catch(() => null);
                if (!resp.ok || !json?.success) {
                    throw new Error(json?.error || json?.message || `HTTP ${resp.status}`);
                }

                clearItemsDraft();
                const batchId = String(json.data.batch_id);
                window.location.href = `/public/placas-0km/${batchId}`;
            } catch (e) {
                showError(String(e?.message || e));
            } finally {
                enqueueSingleBtn.disabled = false;
            }
        });

        resetRunnerBtn.addEventListener('click', async () => {
            clearError();
            setApiKey(apiKeyEl.value);

            const confirmed = window.confirm(
                'Confirma resetar runner (id=1) e voltar request #10 para pending?'
            );
            if (!confirmed) return;

            resetRunnerBtn.disabled = true;
            try {
                const resp = await apiFetch('/api/public/placas-0km/runner/reset', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ runner_id: 1, request_id: 10 })
                });

                const json = await resp.json().catch(() => null);
                if (!resp.ok || !json?.success) {
                    throw new Error(json?.error || json?.message || `HTTP ${resp.status}`);
                }

                await refreshStatus();
                await refreshBatches();
            } catch (e) {
                showError(String(e?.message || e));
            } finally {
                resetRunnerBtn.disabled = false;
            }
        });

        apiKeyEl.addEventListener('input', () => setApiKey(apiKeyEl.value));
        itemsJsonEl.addEventListener('input', () => setItemsDraft(itemsJsonEl.value));
        cpfCgcEl.addEventListener('input', () => setDraft(STORAGE.cpfCgcDraft, cpfCgcEl.value));
        nomeEl.addEventListener('input', () => setDraft(STORAGE.nomeDraft, nomeEl.value));
        chassiEl.addEventListener('input', () => setDraft(STORAGE.chassiDraft, chassiEl.value));
        numerosEl.addEventListener('input', () => setDraft(STORAGE.numerosDraft, numerosEl.value));

        const storedKey = localStorage.getItem(STORAGE.apiKey);
        if (storedKey) apiKeyEl.value = storedKey;
        if (!cpfCgcEl.value) cpfCgcEl.value = getDraft(STORAGE.cpfCgcDraft);
        if (!nomeEl.value) nomeEl.value = getDraft(STORAGE.nomeDraft);
        if (!chassiEl.value) chassiEl.value = getDraft(STORAGE.chassiDraft);
        if (!numerosEl.value) numerosEl.value = getDraft(STORAGE.numerosDraft);
        if (!itemsJsonEl.value) {
            const draft = getItemsDraft();
            if (draft) itemsJsonEl.value = draft;
        }

        refreshBatches();

        if (currentBatchId) {
            startPolling();
        }
    })();
</script>
</body>
</html>
