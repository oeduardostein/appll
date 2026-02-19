@extends('admin.layouts.app')

@section('content')
    <style>
        .placa-zero-km__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        .placa-zero-km__header-copy {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .placa-zero-km__header h1 {
            margin: 0;
            font-size: 24px;
            color: var(--text-strong);
        }

        .placa-zero-km__header p {
            margin: 0;
            color: var(--text-muted);
        }

        .placa-zero-km__header-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text-strong);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 10px 14px;
        }

        .placa-zero-km__grid {
            display: grid;
            gap: 24px;
        }

        @media (min-width: 1024px) {
            .placa-zero-km__grid {
                grid-template-columns: 1.1fr 1fr;
            }
        }

        .placa-zero-km__card {
            padding: 24px;
        }

        .placa-zero-km__form {
            display: grid;
            gap: 18px;
        }

        .placa-zero-km__row {
            display: grid;
            gap: 16px;
        }

        @media (min-width: 720px) {
            .placa-zero-km__row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .placa-zero-km__field {
            display: grid;
            gap: 6px;
        }

        .placa-zero-km__field label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .placa-zero-km__field input,
        .placa-zero-km__field select {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            color: var(--text-strong);
            background: #fff;
            outline: none;
        }

        .placa-zero-km__field input:focus,
        .placa-zero-km__field select:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(11, 78, 162, 0.12);
        }

        .placa-zero-km__actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .placa-zero-km__button {
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            cursor: pointer;
            color: #fff;
            background: var(--brand-primary);
            transition: background 160ms ease, transform 160ms ease;
        }

        .placa-zero-km__button:hover {
            background: var(--brand-primary-hover);
            transform: translateY(-1px);
        }

        .placa-zero-km__button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .placa-zero-km__status {
            font-size: 14px;
            color: var(--text-muted);
        }

        .placa-zero-km__error {
            padding: 12px 14px;
            border-radius: 12px;
            background: #fee2e2;
            color: #991b1b;
            font-size: 14px;
            display: none;
        }

        .placa-zero-km__card[hidden],
        .placa-zero-km__loader[hidden],
        .placa-zero-km__link[hidden] {
            display: none !important;
        }

        .placa-zero-km__result {
            display: grid;
            gap: 16px;
        }

        .placa-zero-km__pill {
            display: inline-flex;
            align-items: center;
            padding: 0;
            border-radius: 0;
            background: transparent;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 12px;
        }

        .placa-zero-km__list {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }

        .placa-zero-km__plate {
            padding: 10px 12px;
            border-radius: 12px;
            background: var(--surface-muted);
            text-align: left;
            font-weight: 600;
            color: var(--text-strong);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .placa-zero-km__plate::before {
            content: '';
            width: 14px;
            height: 14px;
            border-radius: 999px;
            border: 2px solid #bfd0e6;
            background: #fff;
            flex-shrink: 0;
        }

        .placa-zero-km__result-section {
            display: grid;
            gap: 10px;
        }

        .placa-zero-km__result-status {
            font-size: 14px;
            color: var(--text-muted);
        }

        .placa-zero-km__result-status--multiline {
            white-space: pre-wrap;
        }

        .placa-zero-km__link {
            color: var(--brand-primary);
            font-weight: 600;
            text-decoration: none;
        }

        .placa-zero-km__loader {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .placa-zero-km__spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #d3e0ef;
            border-top-color: var(--brand-primary);
            border-radius: 999px;
            animation: placa-zero-km-spin 800ms linear infinite;
        }

        @keyframes placa-zero-km-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .placa-zero-km__json {
            background: #0f172a;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 12px;
            overflow: auto;
            max-height: 420px;
            font-size: 12px;
        }
    </style>

    <div class="placa-zero-km__header">
        <div class="placa-zero-km__header-copy">
            <h1>Consulta de Placas 0KM</h1>
        </div>
        <a class="placa-zero-km__header-link" href="{{ route('admin.placas-0km.queue') }}">Abrir fila com imagens</a>
    </div>

    <div class="placa-zero-km__grid">
        <section class="admin-card placa-zero-km__card">
            <form class="placa-zero-km__form" id="placaZeroKmForm">
                <div class="placa-zero-km__row">
                    <div class="placa-zero-km__field">
                        <label for="cpfCgc">CPF/CNPJ do proprietário (opcional)</label>
                        <input id="cpfCgc" name="cpf_cgc" type="text" placeholder="Somente números">
                    </div>
                    <div class="placa-zero-km__field">
                        <label for="nome">Nome (opcional)</label>
                        <input id="nome" name="nome" type="text" placeholder="Nome do proprietário">
                    </div>
                </div>

                <div class="placa-zero-km__row">
                    <div class="placa-zero-km__field">
                        <label for="chassi">Chassi</label>
                        <input id="chassi" name="chassi" type="text" placeholder="Ex.: 94DFAAP16TB015294" required>
                    </div>
                    <div class="placa-zero-km__field">
                        <label for="numeroTentativa">Número de tentativas</label>
                        <input id="numeroTentativa" name="numero_tentativa" type="number" min="1" max="3" value="3">
                    </div>
                </div>

                <div class="placa-zero-km__row">
                    <div class="placa-zero-km__field">
                        <label for="numeros">Complemento (opcional)</label>
                        <input id="numeros" name="numeros" type="text" maxlength="4" placeholder="Ex.: 1A23">
                    </div>
                </div>

                <div class="placa-zero-km__actions">
                    <button class="placa-zero-km__button" id="consultarButton" type="submit">Enfileirar</button>
                    <span class="placa-zero-km__status" id="statusText">Preencha o chassi para enfileirar.</span>
                </div>
                <div class="placa-zero-km__error" id="errorBox"></div>
            </form>
        </section>

        <section class="admin-card placa-zero-km__card placa-zero-km__result" id="resultCard" hidden>
            <span class="placa-zero-km__pill">Resultado</span>
            <div class="placa-zero-km__result-section">
                <strong>Status da fila</strong>
                <div class="placa-zero-km__result-status" id="queueStatusText">Aguardando envio.</div>
                <a class="placa-zero-km__link" id="queueLink" href="#" target="_blank" rel="noopener noreferrer" hidden>Acompanhar na fila</a>
            </div>
            <div class="placa-zero-km__result-section">
                <strong>Placas disponíveis</strong>
                <div class="placa-zero-km__loader" id="processingLoader" hidden>
                    <span class="placa-zero-km__spinner" aria-hidden="true"></span>
                    <span id="processingText">Processando consulta...</span>
                </div>
                <div class="placa-zero-km__list" id="platesList"></div>
                <div class="placa-zero-km__result-status placa-zero-km__result-status--multiline" id="resultText"></div>
            </div>
        </section>
    </div>

    <script>
        (function() {
            const ENQUEUE_URL = '{{ url('/api/public/placas-0km/batches') }}';
            const QUEUE_URL_BASE = '{{ route('admin.placas-0km.queue') }}';
            const QUEUE_BATCH_SHOW_BASE_URL = '{{ url('/api/public/placas-0km/batches') }}';
            const PUBLIC_API_KEY = @json((string) config('services.public_placas0km.key', ''));
            const POLL_INTERVAL_MS = 2000;
            const SLOW_NOTICE_AFTER_MS = 180000;

            const form = document.getElementById('placaZeroKmForm');
            const statusText = document.getElementById('statusText');
            const errorBox = document.getElementById('errorBox');
            const button = document.getElementById('consultarButton');
            const resultCard = document.getElementById('resultCard');
            const platesList = document.getElementById('platesList');
            const queueStatusText = document.getElementById('queueStatusText');
            const queueLink = document.getElementById('queueLink');
            const processingLoader = document.getElementById('processingLoader');
            const processingText = document.getElementById('processingText');
            const resultText = document.getElementById('resultText');

            let pollingTimer = null;
            let pollingStartedAt = 0;
            let pollingInFlight = false;
            let activeBatchId = null;
            let activeRequestId = null;

            function normalizeDigits(value) {
                return (value || '').replace(/\D/g, '');
            }

            function normalizeUpper(value) {
                return (value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            }

            function setLoading(loading, text) {
                button.disabled = loading;
                if (typeof text === 'string') {
                    statusText.textContent = text;
                    return;
                }
                statusText.textContent = loading ? 'Enfileirando, aguarde...' : 'Preencha o chassi para enfileirar.';
            }

            function setError(message) {
                if (!message) {
                    errorBox.style.display = 'none';
                    errorBox.textContent = '';
                    return;
                }
                errorBox.textContent = message;
                errorBox.style.display = 'block';
            }

            function withPublicApiHeaders(baseHeaders = {}) {
                const headers = { ...baseHeaders };
                if (PUBLIC_API_KEY) {
                    headers['X-Public-Api-Key'] = PUBLIC_API_KEY;
                }
                return headers;
            }

            function toSafeNumber(value) {
                const parsed = Number(value);
                return Number.isFinite(parsed) ? parsed : 0;
            }

            function normalizePlate(value) {
                const compact = normalizeUpper(value);
                if (compact.length !== 7) return compact;
                return `${compact.slice(0, 3)}-${compact.slice(3)}`;
            }

            function mergeUniquePlates(...groups) {
                const unique = new Set();
                for (const group of groups) {
                    if (!Array.isArray(group)) continue;
                    for (const item of group) {
                        const plate = normalizePlate(item);
                        if (!plate) continue;
                        unique.add(plate);
                    }
                }
                return Array.from(unique);
            }

            function extractRequestPlates(requestRow) {
                const payload = requestRow?.response_payload;
                const ocrPlates = payload?.data?.ocr?.plates;
                const detectedPlates = payload?.data?.placas;
                return mergeUniquePlates(ocrPlates, detectedPlates);
            }

            function extractModalErrorText(requestRow) {
                const payload = requestRow?.response_payload;
                const ocrText = payload?.data?.ocr?.text;
                const responseError = requestRow?.response_error;
                const normalizedOcrText = typeof ocrText === 'string' ? ocrText.trim() : '';
                const normalizedResponseError = typeof responseError === 'string' ? responseError.trim() : '';
                return normalizedResponseError || normalizedOcrText || '';
            }

            function renderPlateItems(plates) {
                platesList.innerHTML = '';
                for (const plate of plates) {
                    const item = document.createElement('div');
                    item.className = 'placa-zero-km__plate';
                    item.textContent = plate;
                    platesList.appendChild(item);
                }
            }

            function showResultCard(batchId, requestId) {
                resultCard.hidden = false;
                if (requestId) {
                    queueStatusText.textContent = `Item enfileirado no batch #${batchId} (req #${requestId}).`;
                } else {
                    queueStatusText.textContent = `Item enfileirado no batch #${batchId}.`;
                }
                queueLink.href = `${QUEUE_URL_BASE}?batch_id=${batchId}`;
                queueLink.hidden = false;
            }

            function setProcessingState(loading, text) {
                processingLoader.hidden = !loading;
                if (text) {
                    processingText.textContent = text;
                }
            }

            function stopPolling() {
                if (pollingTimer) {
                    clearInterval(pollingTimer);
                }
                pollingTimer = null;
                pollingInFlight = false;
            }

            async function fetchBatchStatus(batchId) {
                const response = await fetch(`${QUEUE_BATCH_SHOW_BASE_URL}/${batchId}`, {
                    headers: withPublicApiHeaders({
                        'Accept': 'application/json',
                    }),
                });

                const data = await response.json().catch(() => null);
                if (!response.ok || !data?.success) {
                    throw new Error(data?.error || data?.message || 'Erro ao consultar status da fila.');
                }

                return data.data;
            }

            function updateFromBatchStatus(data) {
                const batch = data?.batch ?? null;
                const requests = Array.isArray(data?.requests) ? data.requests : [];
                const requestRow = requests.find((item) => toSafeNumber(item?.id) === activeRequestId) ?? requests[0] ?? null;
                if (!activeRequestId && requestRow?.id) {
                    activeRequestId = toSafeNumber(requestRow.id);
                }

                const requestStatus = String(requestRow?.status || '');
                const batchStatus = String(batch?.status || '');
                const isDone = requestStatus === 'succeeded' || requestStatus === 'failed';
                const retryInfo = requestRow?.response_payload?.data?.transient_retry || null;
                const transientMessage = requestRow?.response_payload?.data?.ocr?.transient_message || '';
                const elapsedMs = Date.now() - pollingStartedAt;
                const queueAhead = toSafeNumber(requestRow?.queue_ahead);
                const queueAheadMessage = queueAhead > 0
                    ? (queueAhead === 1
                        ? 'Há 1 requisição na frente da fila. A sua já vai começar.'
                        : `Há ${queueAhead} requisições na frente da fila. A sua pode demorar um pouco ainda.`)
                    : '';

                if (requestStatus === 'running' || requestStatus === 'pending') {
                    if (retryInfo?.triggered) {
                        const attempts = toSafeNumber(retryInfo?.attempts);
                        const maxRetries = toSafeNumber(retryInfo?.max_retries);
                        const waitMs = toSafeNumber(retryInfo?.wait_ms);
                        const waitSeconds = waitMs > 0 ? Math.max(1, Math.round(waitMs / 1000)) : 8;
                        const progressLabel = maxRetries > 0 ? `${attempts}/${maxRetries}` : `${attempts}`;
                        queueStatusText.textContent = 'O servidor está mais lento do que o normal. Estamos fazendo uma nova tentativa.';
                        setProcessingState(true, `Nova tentativa ${progressLabel}. Aguardando ${waitSeconds}s para reprocessar...`);
                        resultText.textContent = [
                            queueAheadMessage,
                            transientMessage ? `Mensagem detectada na tela: ${transientMessage}` : '',
                        ].filter(Boolean).join('\n');
                    } else {
                        if (elapsedMs > SLOW_NOTICE_AFTER_MS) {
                            queueStatusText.textContent = 'A consulta está demorando mais que o normal. Aguarde, estamos tentando novamente.';
                            setProcessingState(true, 'Servidor mais lento que o normal. Fazendo nova tentativa...');
                            resultText.textContent = [
                                queueAheadMessage,
                                'Seguimos consultando automaticamente até retornar placas ou erro do sistema.',
                            ].filter(Boolean).join('\n');
                        } else {
                            queueStatusText.textContent = `Processando requisição #${activeRequestId} no batch #${activeBatchId}...`;
                            setProcessingState(true, 'Executando script e analisando resultado...');
                            resultText.textContent = queueAheadMessage;
                        }
                    }
                    renderPlateItems([]);
                    return false;
                }

                if (isDone || batchStatus === 'completed') {
                    setProcessingState(false);

                    if (requestStatus === 'failed') {
                        queueStatusText.textContent = `Falha na requisição #${activeRequestId}.`;
                        const exactErrorText = extractModalErrorText(requestRow);
                        resultText.textContent = exactErrorText || 'Consulta finalizada com falha.';
                        renderPlateItems([]);
                        return true;
                    }

                    const plates = extractRequestPlates(requestRow);
                    queueStatusText.textContent = `Requisição #${activeRequestId} concluída com sucesso.`;
                    renderPlateItems(plates);
                    resultText.textContent = plates.length
                        ? `${plates.length} placa(s) encontrada(s).`
                        : 'Nenhuma placa listada para esta consulta.';
                    return true;
                }

                queueStatusText.textContent = `Batch #${activeBatchId} em processamento...`;
                setProcessingState(true, 'Aguardando processamento da fila...');
                return false;
            }

            async function pollBatchStatus() {
                if (!activeBatchId || pollingInFlight) return;
                pollingInFlight = true;
                try {
                    const data = await fetchBatchStatus(activeBatchId);
                    setError('');
                    const finished = updateFromBatchStatus(data);
                    if (finished) {
                        stopPolling();
                        setLoading(false, 'Consulta finalizada.');
                        return;
                    }
                } catch (error) {
                    setError(error?.message || 'Falha ao buscar status da fila. Tentando novamente...');
                    queueStatusText.textContent = 'Falha temporária ao atualizar. Vamos tentar novamente automaticamente.';
                    setProcessingState(true, 'Reconectando para continuar acompanhamento...');
                } finally {
                    pollingInFlight = false;
                }
            }

            function startPolling(batchId, requestId) {
                stopPolling();
                activeBatchId = toSafeNumber(batchId);
                activeRequestId = toSafeNumber(requestId);
                pollingStartedAt = Date.now();
                pollBatchStatus();
                pollingTimer = setInterval(pollBatchStatus, POLL_INTERVAL_MS);
            }

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                stopPolling();
                setError('');
                setLoading(true, 'Enfileirando, aguarde...');
                let enqueued = false;
                let batchId = 0;
                let requestId = 0;

                const payload = {
                    items: [{
                        cpf_cgc: normalizeDigits(document.getElementById('cpfCgc').value),
                        nome: (document.getElementById('nome').value || '').trim(),
                        chassi: normalizeUpper(document.getElementById('chassi').value),
                        numeros: normalizeUpper(document.getElementById('numeros').value),
                    }],
                };

                try {
                    const response = await fetch(ENQUEUE_URL, {
                        method: 'POST',
                        headers: withPublicApiHeaders({
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        }),
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json().catch(() => null);
                    if (!response.ok || !data) {
                        throw new Error(data?.error || data?.message || 'Erro ao consultar placas.');
                    }

                    if (!data.success) {
                        throw new Error(data.error || 'Falha ao enfileirar.');
                    }

                    batchId = toSafeNumber(data?.data?.batch_id);
                    requestId = toSafeNumber(data?.data?.request_id);
                    if (!batchId) {
                        throw new Error('Resposta inválida ao enfileirar item.');
                    }

                    showResultCard(batchId, requestId);
                    setProcessingState(true, 'Aguardando início da execução...');
                    resultText.textContent = '';
                    renderPlateItems([]);
                    enqueued = true;
                } catch (error) {
                    setError(error?.message || 'Erro ao enfileirar solicitação.');
                } finally {
                    setLoading(false);
                    if (enqueued) {
                        statusText.textContent = 'Enfileirado com sucesso. Aguardando conclusão...';
                        startPolling(batchId, requestId);
                    }
                }
            });
        })();
    </script>
@endsection
