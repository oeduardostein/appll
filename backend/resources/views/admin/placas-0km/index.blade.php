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

        .placa-zero-km__plate-field {
            display: grid;
            gap: 8px;
        }

        .placa-zero-km__plate-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .placa-zero-km__plate-option {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 600;
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
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }

        .placa-zero-km__plate {
            padding: 10px 12px;
            border-radius: 12px;
            background: var(--surface-muted);
            text-align: center;
            font-weight: 600;
            color: var(--text-strong);
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
                        <label for="cpfCgc">CPF/CNPJ do proprietário</label>
                        <input id="cpfCgc" name="cpf_cgc" type="text" placeholder="Somente números" required>
                    </div>
                    <div class="placa-zero-km__field">
                        <label for="chassi">Chassi</label>
                        <input id="chassi" name="chassi" type="text" placeholder="Ex.: 94DFAAP16TB015294" required>
                    </div>
                </div>

                <div class="placa-zero-km__row">
                    <div class="placa-zero-km__field">
                        <label for="numeros">Complemento (opcional)</label>
                        <input id="numeros" name="numeros" type="text" maxlength="4" placeholder="Ex.: 1A23">
                    </div>
                    <div class="placa-zero-km__field">
                        <label for="numeroTentativa">Número de tentativas</label>
                        <input id="numeroTentativa" name="numero_tentativa" type="number" min="1" max="3" value="3">
                    </div>
                    <div class="placa-zero-km__field">
                        <label for="placaEscolhaAnterior">Placa escolhida anteriormente (opcional)</label>
                        <div class="placa-zero-km__plate-field">
                            <div class="placa-zero-km__plate-options">
                                <label class="placa-zero-km__plate-option">
                                    <input type="radio" name="placa_escolha_anterior_format" value="antiga">
                                    <span>Antiga (ABC-1234)</span>
                                </label>
                                <label class="placa-zero-km__plate-option">
                                    <input type="radio" name="placa_escolha_anterior_format" value="mercosul">
                                    <span>Mercosul (ABC-1D23)</span>
                                </label>
                            </div>
                            <input id="placaEscolhaAnterior" name="placa_escolha_anterior" type="text" maxlength="8" placeholder="Opcional: selecione o padrão da placa" disabled>
                        </div>
                    </div>
                </div>

                <div class="placa-zero-km__field">
                    <label for="tipoRestricao">Restrição financeira</label>
                    <select id="tipoRestricao" name="tipo_restricao_financeira">
                        <option value="-1">Todas</option>
                        <option value="0">Sem restrição</option>
                        <option value="1">Com restrição</option>
                    </select>
                </div>

                <div class="placa-zero-km__actions">
                    <button class="placa-zero-km__button" id="consultarButton" type="submit">Consultar</button>
                    <span class="placa-zero-km__status" id="statusText">Preencha os campos para consultar.</span>
                </div>
                <div class="placa-zero-km__error" id="errorBox"></div>
            </form>
        </section>

        <section class="admin-card placa-zero-km__card placa-zero-km__result" id="resultCard" hidden>
            <span class="placa-zero-km__pill">Resultado</span>
            <div>
                <strong>Placas disponíveis</strong>
                <div class="placa-zero-km__list" id="platesList"></div>
            </div>
        </section>
    </div>

    <script>
        (function() {
            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const CONSULTAR_URL = '{{ route('admin.placas-0km.consultar') }}';

            const form = document.getElementById('placaZeroKmForm');
            const statusText = document.getElementById('statusText');
            const errorBox = document.getElementById('errorBox');
            const button = document.getElementById('consultarButton');
            const resultCard = document.getElementById('resultCard');
            const platesList = document.getElementById('platesList');

            const PLATE_FORMAT_ANTIGA = 'antiga';
            const PLATE_FORMAT_MERCOSUL = 'mercosul';
            const oldPlatePattern = /^[A-Z]{3}-[0-9]{4}$/;
            const mercosulPlatePattern = /^[A-Z]{3}-[0-9][A-Z0-9][0-9]{2}$/;

            const placaEscolhaAnteriorInput = document.getElementById('placaEscolhaAnterior');
            const placaEscolhaAnteriorFormatInputs = Array.from(
                document.querySelectorAll('input[name="placa_escolha_anterior_format"]')
            );

            function normalizeDigits(value) {
                return (value || '').replace(/\D/g, '');
            }

            function normalizeUpper(value) {
                return (value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            }

            function normalizePlateChars(value) {
                return (value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            }

            function formatPlate(value, format) {
                const cleaned = normalizePlateChars(value);

                if (format === PLATE_FORMAT_ANTIGA) {
                    let letters = '';
                    let digits = '';
                    for (const char of cleaned) {
                        if (letters.length < 3) {
                            if (/[A-Z]/.test(char)) {
                                letters += char;
                            }
                            continue;
                        }
                        if (digits.length < 4 && /[0-9]/.test(char)) {
                            digits += char;
                        }
                    }
                    return letters.length === 3 ? `${letters}-${digits}` : letters;
                }

                if (format === PLATE_FORMAT_MERCOSUL) {
                    let letters = '';
                    let digit = '';
                    let middle = '';
                    let lastDigits = '';
                    for (const char of cleaned) {
                        if (letters.length < 3) {
                            if (/[A-Z]/.test(char)) {
                                letters += char;
                            }
                            continue;
                        }
                        if (digit === '') {
                            if (/[0-9]/.test(char)) {
                                digit = char;
                            }
                            continue;
                        }
                        if (middle === '') {
                            if (/[A-Z0-9]/.test(char)) {
                                middle = char;
                            }
                            continue;
                        }
                        if (lastDigits.length < 2 && /[0-9]/.test(char)) {
                            lastDigits += char;
                        }
                    }
                    return letters.length === 3 ? `${letters}-${digit}${middle}${lastDigits}` : letters;
                }

                return value || '';
            }

            function isValidPlate(value, format) {
                const plate = (value || '').toUpperCase();
                if (format === PLATE_FORMAT_ANTIGA) {
                    return oldPlatePattern.test(plate);
                }
                if (format === PLATE_FORMAT_MERCOSUL) {
                    return mercosulPlatePattern.test(plate);
                }
                return false;
            }

            function resetPlateFormatField() {
                placaEscolhaAnteriorFormatInputs.forEach((radio) => {
                    radio.checked = false;
                });
                placaEscolhaAnteriorInput.dataset.plateFormat = '';
                placaEscolhaAnteriorInput.disabled = true;
                placaEscolhaAnteriorInput.value = '';
                placaEscolhaAnteriorInput.placeholder = 'Opcional: selecione o padrão da placa';
            }

            function applyPlateFormatSelection(format) {
                placaEscolhaAnteriorInput.dataset.plateFormat = format;
                placaEscolhaAnteriorInput.disabled = !format;
                placaEscolhaAnteriorInput.value = '';
                placaEscolhaAnteriorInput.placeholder = format === PLATE_FORMAT_ANTIGA ? 'ABC-1234' : 'ABC-1D23';
                if (!placaEscolhaAnteriorInput.disabled) {
                    setTimeout(() => placaEscolhaAnteriorInput.focus(), 0);
                }
            }

            resetPlateFormatField();
            placaEscolhaAnteriorFormatInputs.forEach((radio) => {
                radio.addEventListener('change', () => applyPlateFormatSelection(radio.value));
            });
            placaEscolhaAnteriorInput.addEventListener('input', () => {
                const format = placaEscolhaAnteriorInput.dataset.plateFormat;
                if (!format) {
                    placaEscolhaAnteriorInput.value = '';
                    return;
                }
                placaEscolhaAnteriorInput.value = formatPlate(placaEscolhaAnteriorInput.value, format);
            });

            function setLoading(loading) {
                button.disabled = loading;
                statusText.textContent = loading ? 'Consultando, aguarde...' : 'Preencha os campos para consultar.';
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

            function renderResult(payload) {
                const plates = payload?.data?.placas ?? [];
                platesList.innerHTML = '';
                if (plates.length === 0) {
                    platesList.innerHTML = '<div class="placa-zero-km__plate">Nenhuma placa listada</div>';
                } else {
                    plates.forEach((plate) => {
                        const item = document.createElement('div');
                        item.className = 'placa-zero-km__plate';
                        item.textContent = plate;
                        platesList.appendChild(item);
                    });
                }

                resultCard.hidden = false;
            }

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                setError('');
                setLoading(true);

                const placaEscolhaAnteriorValue = placaEscolhaAnteriorInput.value.trim();
                if (placaEscolhaAnteriorValue) {
                    const format = placaEscolhaAnteriorInput.dataset.plateFormat || '';
                    if (!format) {
                        setError('Selecione o padrão da placa escolhida anteriormente.');
                        setLoading(false);
                        return;
                    }
                    const formatted = formatPlate(placaEscolhaAnteriorValue, format);
                    placaEscolhaAnteriorInput.value = formatted;
                    if (!isValidPlate(formatted, format)) {
                        setError('Placa escolhida anteriormente inválida.');
                        setLoading(false);
                        return;
                    }
                }

                const payload = {
                    cpf_cgc: normalizeDigits(document.getElementById('cpfCgc').value),
                    chassi: normalizeUpper(document.getElementById('chassi').value),
                    numeros: normalizeUpper(document.getElementById('numeros').value),
                    numero_tentativa: document.getElementById('numeroTentativa').value,
                    placa_escolha_anterior: normalizeUpper(document.getElementById('placaEscolhaAnterior').value),
                    tipo_restricao_financeira: document.getElementById('tipoRestricao').value,
                };

                try {
                    const response = await fetch(CONSULTAR_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json().catch(() => null);
                    if (!response.ok || !data) {
                        throw new Error(data?.error || data?.message || 'Erro ao consultar placas.');
                    }

                    if (!data.success) {
                        throw new Error(data.error || 'Consulta retornou erro.');
                    }

                    renderResult(data);
                } catch (error) {
                    setError(error?.message || 'Erro ao consultar placas.');
                } finally {
                    setLoading(false);
                }
            });
        })();
    </script>
@endsection
