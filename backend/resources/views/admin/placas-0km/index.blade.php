@extends('admin.layouts.app')

@section('content')
    <style>
        .placa-zero-km__header {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 28px;
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

        .placa-zero-km__result {
            display: grid;
            gap: 16px;
        }

        .placa-zero-km__pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            background: #e0edff;
            color: var(--brand-primary);
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
        <h1>Consulta de Placas 0KM</h1>
        <p>Consulta direta no eCRV com 18 prefixos fixos e retorno em JSON.</p>
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
                        <input id="placaEscolhaAnterior" name="placa_escolha_anterior" type="text" maxlength="7" placeholder="Ex.: ABC1D23">
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

            function normalizeDigits(value) {
                return (value || '').replace(/\D/g, '');
            }

            function normalizeUpper(value) {
                return (value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            }

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
