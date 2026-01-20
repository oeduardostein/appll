<div class="be-overlay hidden" id="crlvCaptchaOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="crlvCaptchaTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="crlvCaptchaTitle">Emissão do CRLV-e</h2>
            <button class="be-dialog-close" type="button" id="crlvCaptchaClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <input
                class="be-input"
                id="crlvCaptchaPlate"
                type="text"
                placeholder="Placa"
                maxlength="7"
                autocomplete="off"
                disabled
            >
            <input
                class="be-input"
                id="crlvCaptchaRenavam"
                type="text"
                placeholder="Renavam"
                maxlength="11"
                autocomplete="off"
                disabled
            >
            <input
                class="be-input"
                id="crlvCaptchaDocument"
                type="text"
                placeholder="CPF / CNPJ"
                maxlength="14"
                autocomplete="off"
                disabled
            >
            <div class="be-captcha-box">
                <div class="be-captcha-header">
                    <span>Captcha</span>
                    <button class="be-captcha-refresh" type="button" id="crlvCaptchaRefresh">Atualizar</button>
                </div>
                <div class="be-captcha-image" id="crlvCaptchaImageWrap">
                    <div class="be-captcha-loading" id="crlvCaptchaLoading">
                        <span class="spinner"></span>
                        <span>Carregando captcha...</span>
                    </div>
                    <img id="crlvCaptchaImage" alt="Captcha">
                </div>
                <input
                    class="be-input"
                    id="crlvCaptchaInput"
                    type="text"
                    placeholder="Digite o captcha"
                    maxlength="10"
                    autocomplete="off"
                >
            </div>
            <div class="be-dialog-error" id="crlvCaptchaError"></div>
            <button class="be-dialog-submit" type="button" id="crlvCaptchaSubmit">
                <span class="be-btn-text">Emitir</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="crlvCaptchaCancel">Cancelar</button>
        </div>
    </div>
</div>
