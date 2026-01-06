<div class="be-overlay hidden" id="otherStatesCaptchaOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="otherStatesCaptchaTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="otherStatesCaptchaTitle">Base de outros estados</h2>
            <button class="be-dialog-close" type="button" id="otherStatesCaptchaClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <p class="be-dialog-helper">Informe o captcha exibido para continuar.</p>
            <input
                class="be-input"
                id="otherStatesCaptchaChassi"
                type="text"
                placeholder="Chassi"
                maxlength="17"
                autocomplete="off"
                disabled
            >
            <select class="be-input be-select" id="otherStatesCaptchaUf" disabled>
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
            <div class="be-captcha-box">
                <div class="be-captcha-header">
                    <span>Captcha</span>
                    <button class="be-captcha-refresh" type="button" id="otherStatesCaptchaRefresh">Atualizar</button>
                </div>
                <div class="be-captcha-image" id="otherStatesCaptchaImageWrap">
                    <div class="be-captcha-loading" id="otherStatesCaptchaLoading">
                        <span class="spinner"></span>
                        <span>Carregando captcha...</span>
                    </div>
                    <img id="otherStatesCaptchaImage" alt="Captcha">
                </div>
                <input
                    class="be-input"
                    id="otherStatesCaptchaInput"
                    type="text"
                    placeholder="Digite o captcha"
                    maxlength="10"
                    autocomplete="off"
                >
            </div>
            <div class="be-dialog-error" id="otherStatesCaptchaError"></div>
            <button class="be-dialog-submit" type="button" id="otherStatesCaptchaSubmit">
                <span class="be-btn-text">Pesquisar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="otherStatesCaptchaCancel">Cancelar</button>
        </div>
    </div>
</div>
