<div class="be-overlay hidden" id="renainfCaptchaOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="renainfCaptchaTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="renainfCaptchaTitle">Consulta RENAINF</h2>
            <button class="be-dialog-close" type="button" id="renainfCaptchaClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <p class="be-dialog-helper">Digite o captcha exibido para continuar.</p>
            <input
                class="be-input"
                id="renainfCaptchaPlate"
                type="text"
                placeholder="Placa"
                maxlength="8"
                autocomplete="off"
                disabled
            >
            <select class="be-input be-select" id="renainfCaptchaStatus" disabled>
                <option value="2">Todas</option>
                <option value="1">Multas em cobrança</option>
            </select>
            <select class="be-input be-select" id="renainfCaptchaUf" disabled>
                <option value="">Selecione a UF</option>
                <option value="AC">AC</option>
                <option value="AL">AL</option>
                <option value="AP">AP</option>
                <option value="AM">AM</option>
                <option value="BA">BA</option>
                <option value="CE">CE</option>
                <option value="DF">DF</option>
                <option value="ES">ES</option>
                <option value="GO">GO</option>
                <option value="MA">MA</option>
                <option value="MT">MT</option>
                <option value="MS">MS</option>
                <option value="MG">MG</option>
                <option value="PA">PA</option>
                <option value="PB">PB</option>
                <option value="PR">PR</option>
                <option value="PE">PE</option>
                <option value="PI">PI</option>
                <option value="RJ">RJ</option>
                <option value="RN">RN</option>
                <option value="RS">RS</option>
                <option value="RO">RO</option>
                <option value="RR">RR</option>
                <option value="SC">SC</option>
                <option value="SP">SP</option>
                <option value="SE">SE</option>
                <option value="TO">TO</option>
            </select>
            <div class="be-field-group">
                <input
                    class="be-input"
                    id="renainfCaptchaStart"
                    type="date"
                    placeholder="Data inicial"
                    disabled
                >
                <input
                    class="be-input"
                    id="renainfCaptchaEnd"
                    type="date"
                    placeholder="Data final"
                    disabled
                >
            </div>
            <div class="be-captcha-box">
                <div class="be-captcha-header">
                    <span>Captcha</span>
                    <button class="be-captcha-refresh" type="button" id="renainfCaptchaRefresh">Atualizar</button>
                </div>
                <div class="be-captcha-image" id="renainfCaptchaImageWrap">
                    <div class="be-captcha-loading" id="renainfCaptchaLoading">
                        <span class="spinner"></span>
                        <span>Carregando captcha...</span>
                    </div>
                    <img id="renainfCaptchaImage" alt="Captcha">
                </div>
                <input
                    class="be-input"
                    id="renainfCaptchaInput"
                    type="text"
                    placeholder="Digite o captcha"
                    maxlength="10"
                    autocomplete="off"
                >
            </div>
            <div class="be-dialog-error" id="renainfCaptchaError"></div>
            <button class="be-dialog-submit" type="button" id="renainfCaptchaSubmit">
                <span class="be-btn-text">Pesquisar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="renainfCaptchaCancel">Cancelar</button>
        </div>
    </div>
</div>
