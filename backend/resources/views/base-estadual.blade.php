<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Consulta Base Estadual - LL Despachante</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 32px 24px;
            text-align: center;
            color: white;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .form-container {
            padding: 32px 24px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            font-size: 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            transition: all 0.3s;
            text-transform: uppercase;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input.error {
            background-color: #FFE5E5;
            border-color: #f44336;
        }

        .error-message {
            color: #f44336;
            font-size: 13px;
            margin-top: 6px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .captcha-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        @media (min-width: 640px) {
            .captcha-container {
                grid-template-columns: 1fr 1fr;
            }
        }

        .captcha-image {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 12px;
            background: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .captcha-image:hover {
            border-color: #667eea;
            background: #f0f0ff;
        }

        .captcha-image img {
            max-width: 100%;
            height: auto;
        }

        .captcha-image.loading {
            color: #999;
            font-size: 14px;
        }

        .btn {
            width: 100%;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn:active:not(:disabled) {
            transform: translateY(0);
        }

        .loading {
            display: none;
            text-align: center;
            padding: 24px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 12px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .result-container {
            display: none;
            margin-top: 24px;
            padding: 24px;
            background: #f9f9f9;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
        }

        .result-container.show {
            display: block;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e0e0e0;
        }

        .result-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .result-content {
            max-height: 500px;
            overflow-y: auto;
        }

        .result-content pre {
            background: white;
            padding: 16px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.6;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert.error {
            background: #FFE5E5;
            border: 2px solid #f44336;
            color: #c62828;
        }

        .alert.success {
            background: #E8F5E9;
            border: 2px solid #4caf50;
            color: #2e7d32;
        }

        .info-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        @media (min-width: 640px) {
            .info-row {
                grid-template-columns: 1fr 1fr;
            }
        }

        .info-item {
            background: white;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .info-item label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            display: block;
            margin-bottom: 4px;
        }

        .info-item value {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Consulta Base Estadual</h1>
            <p>Pesquise informações de veículos pela placa e RENAVAM</p>
        </div>

        <div class="form-container">
            <div class="alert error" id="errorAlert"></div>
            <div class="alert success" id="successAlert"></div>

            <form id="searchForm">
                <div class="form-group">
                    <label for="placa">Placa</label>
                    <input 
                        type="text" 
                        id="placa" 
                        name="placa" 
                        placeholder="ABC1234" 
                        maxlength="7"
                        autocomplete="off"
                        required
                    >
                    <div class="error-message" id="placaError"></div>
                </div>

                <div class="form-group">
                    <label for="renavam">RENAVAM</label>
                    <input 
                        type="text" 
                        id="renavam" 
                        name="renavam" 
                        placeholder="12345678901" 
                        maxlength="11"
                        autocomplete="off"
                    >
                    <div class="error-message" id="renavamError"></div>
                </div>

                <div class="form-group">
                    <label>Captcha</label>
                    <div class="captcha-container">
                        <div class="captcha-image" id="captchaImage">
                            <span class="loading">Clique para carregar captcha</span>
                        </div>
                        <input 
                            type="text" 
                            id="captcha" 
                            name="captcha" 
                            placeholder="Digite o captcha" 
                            maxlength="10"
                            autocomplete="off"
                            required
                            style="text-transform: uppercase;"
                        >
                    </div>
                    <div class="error-message" id="captchaError"></div>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    Consultar
                </button>
            </form>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Consultando base estadual...</p>
            </div>

            <div class="result-container" id="resultContainer">
                <div class="result-header">
                    <h2>Resultado da Consulta</h2>
                    <button class="btn" style="width: auto; padding: 8px 16px; font-size: 14px;" onclick="copyResult()">
                        Copiar
                    </button>
                </div>
                <div class="result-content" id="resultContent"></div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = window.location.origin;
        let captchaBase64 = null;

        // Carregar captcha ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            loadCaptcha();
            
            // Auto-resolver captcha ao clicar na imagem
            document.getElementById('captchaImage').addEventListener('click', function() {
                loadCaptcha();
            });

            // Formatação automática para maiúsculas
            document.getElementById('placa').addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });

            document.getElementById('renavam').addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });

            document.getElementById('captcha').addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase();
            });

            // Validação onBlur
            document.getElementById('placa').addEventListener('blur', function() {
                validateField('placa', this.value.trim() !== '', 'Este campo é obrigatório');
            });

            document.getElementById('captcha').addEventListener('blur', function() {
                validateField('captcha', this.value.trim() !== '', 'Este campo é obrigatório');
            });

            // Submissão do formulário
            document.getElementById('searchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                performSearch();
            });
        });

        async function loadCaptcha() {
            const captchaImage = document.getElementById('captchaImage');
            captchaImage.innerHTML = '<span class="loading">Carregando captcha...</span>';
            captchaImage.classList.add('loading');

            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha`);
                
                if (!response.ok) {
                    throw new Error('Falha ao carregar captcha');
                }

                const contentType = response.headers.get('content-type');
                
                if (contentType && contentType.includes('image')) {
                    const blob = await response.blob();
                    const reader = new FileReader();
                    reader.onloadend = function() {
                        captchaBase64 = reader.result;
                        captchaImage.innerHTML = `<img src="${reader.result}" alt="Captcha">`;
                        captchaImage.classList.remove('loading');
                    };
                    reader.readAsDataURL(blob);
                } else {
                    const text = await response.text();
                    try {
                        const json = JSON.parse(text);
                        captchaBase64 = json.captcha || json.data || text;
                    } catch {
                        captchaBase64 = text;
                    }
                    
                    if (captchaBase64.startsWith('data:image')) {
                        captchaImage.innerHTML = `<img src="${captchaBase64}" alt="Captcha">`;
                    } else {
                        captchaImage.innerHTML = `<span style="font-size: 24px; font-weight: bold; letter-spacing: 4px;">${captchaBase64}</span>`;
                    }
                    captchaImage.classList.remove('loading');
                }
            } catch (error) {
                captchaImage.innerHTML = '<span style="color: #f44336;">Erro ao carregar captcha. Clique para tentar novamente.</span>';
                captchaImage.classList.remove('loading');
                showError('Não foi possível carregar o captcha. Tente novamente.');
            }
        }

        function validateField(fieldName, isValid, errorMessage) {
            const field = document.getElementById(fieldName);
            const errorDiv = document.getElementById(fieldName + 'Error');
            
            if (!isValid) {
                field.classList.add('error');
                errorDiv.textContent = errorMessage;
                errorDiv.classList.add('show');
            } else {
                field.classList.remove('error');
                errorDiv.classList.remove('show');
            }
        }

        function clearErrors() {
            document.querySelectorAll('.error-message').forEach(el => {
                el.classList.remove('show');
            });
            document.querySelectorAll('input').forEach(el => {
                el.classList.remove('error');
            });
        }

        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.textContent = message;
            alert.classList.add('show');
            setTimeout(() => alert.classList.remove('show'), 5000);
        }

        function showSuccess(message) {
            const alert = document.getElementById('successAlert');
            alert.textContent = message;
            alert.classList.add('show');
            setTimeout(() => alert.classList.remove('show'), 3000);
        }

        async function performSearch() {
            clearErrors();

            const placa = document.getElementById('placa').value.trim().toUpperCase();
            const renavam = document.getElementById('renavam').value.trim();
            const captcha = document.getElementById('captcha').value.trim().toUpperCase();

            // Validação
            let hasError = false;

            if (!placa) {
                validateField('placa', false, 'Este campo é obrigatório');
                hasError = true;
            }

            if (!captcha) {
                validateField('captcha', false, 'Este campo é obrigatório');
                hasError = true;
            }

            if (hasError) {
                return;
            }

            // Mostrar loading
            document.getElementById('loading').classList.add('show');
            document.getElementById('resultContainer').classList.remove('show');
            document.getElementById('submitBtn').disabled = true;

            try {
                // Tentar resolver captcha automaticamente primeiro
                let captchaToUse = captcha;
                
                try {
                    const solveResponse = await fetch(`${API_BASE_URL}/api/captcha/solve`);
                    if (solveResponse.ok) {
                        const solveData = await solveResponse.json();
                        if (solveData.solution) {
                            captchaToUse = solveData.solution.trim().toUpperCase();
                            document.getElementById('captcha').value = captchaToUse;
                        }
                    }
                } catch (e) {
                    // Se falhar, usar o captcha manual
                }

                // Fazer a consulta
                const params = new URLSearchParams({
                    placa: placa,
                    renavam: renavam || '',
                    captcha: captchaToUse
                });

                const response = await fetch(`${API_BASE_URL}/api/base-estadual?${params}`);

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'Erro ao consultar base estadual' }));
                    throw new Error(errorData.message || `Erro HTTP ${response.status}`);
                }

                const result = await response.json();
                
                // Exibir resultado
                displayResult(result, placa, renavam);

            } catch (error) {
                showError(error.message || 'Não foi possível consultar a base estadual.');
            } finally {
                document.getElementById('loading').classList.remove('show');
                document.getElementById('submitBtn').disabled = false;
                // Recarregar captcha após consulta
                loadCaptcha();
                document.getElementById('captcha').value = '';
            }
        }

        function displayResult(data, placa, renavam) {
            const container = document.getElementById('resultContainer');
            const content = document.getElementById('resultContent');

            // Verificar se há dados estruturados
            if (data.veiculo || data.fonte) {
                let html = '<div class="info-row">';
                
                if (data.veiculo) {
                    html += `
                        <div class="info-item">
                            <label>Placa</label>
                            <value>${data.veiculo.placa || '—'}</value>
                        </div>
                        <div class="info-item">
                            <label>RENAVAM</label>
                            <value>${data.veiculo.renavam || '—'}</value>
                        </div>
                    `;
                    
                    if (data.veiculo.marca) {
                        html += `
                            <div class="info-item">
                                <label>Marca/Modelo</label>
                                <value>${data.veiculo.marca || '—'}</value>
                            </div>
                        `;
                    }
                    
                    if (data.veiculo.ano_modelo || data.veiculo.ano_fabricacao) {
                        html += `
                            <div class="info-item">
                                <label>Ano</label>
                                <value>${data.veiculo.ano_modelo || '—'} / ${data.veiculo.ano_fabricacao || '—'}</value>
                            </div>
                        `;
                    }
                }

                if (data.proprietario && data.proprietario.nome) {
                    html += `
                        <div class="info-item">
                            <label>Proprietário</label>
                            <value>${data.proprietario.nome}</value>
                        </div>
                    `;
                }

                html += '</div>';
                
                // Adicionar JSON completo
                html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                content.innerHTML = html;
            } else if (data.message) {
                content.innerHTML = `<div style="padding: 16px; background: white; border-radius: 8px;"><p style="font-size: 16px; color: #333;">${data.message}</p></div>`;
            } else {
                content.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            }

            container.classList.add('show');
            showSuccess('Consulta realizada com sucesso!');
        }

        function copyResult() {
            const content = document.getElementById('resultContent');
            const text = content.innerText || content.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                showSuccess('Resultado copiado para a área de transferência!');
            }).catch(() => {
                showError('Não foi possível copiar o resultado.');
            });
        }
    </script>
</body>
</html>

