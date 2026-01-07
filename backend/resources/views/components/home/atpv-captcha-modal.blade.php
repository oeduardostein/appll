<div class="be-overlay hidden" id="atpvCaptchaOverlay" aria-hidden="true">
    <div class="be-dialog atpv-dialog" role="dialog" aria-modal="true" aria-labelledby="atpvCaptchaTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="atpvCaptchaTitle">Informe o captcha</h2>
            <button class="be-dialog-close" type="button" id="atpvCaptchaClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <input
                class="be-input"
                id="atpvCaptchaPlate"
                type="text"
                placeholder="Placa"
                maxlength="7"
                autocomplete="off"
                disabled
            >
            <input
                class="be-input"
                id="atpvCaptchaRenavam"
                type="text"
                placeholder="Renavam"
                maxlength="11"
                autocomplete="off"
                disabled
            >
            <div class="be-captcha-box">
                <div class="be-captcha-header">
                    <span>Captcha</span>
                    <button class="be-captcha-refresh" type="button" id="atpvCaptchaRefresh">Atualizar</button>
                </div>
                <div class="be-captcha-image" id="atpvCaptchaImageWrap">
                    <div class="be-captcha-loading" id="atpvCaptchaLoading">
                        <span class="spinner"></span>
                        <span>Carregando captcha...</span>
                    </div>
                    <img id="atpvCaptchaImage" alt="Captcha">
                </div>
                <input
                    class="be-input"
                    id="atpvCaptchaInput"
                    type="text"
                    placeholder="Digite o captcha"
                    maxlength="10"
                    autocomplete="off"
                >
            </div>
            <div class="be-dialog-error" id="atpvCaptchaError"></div>
            <button class="be-dialog-submit" type="button" id="atpvCaptchaSubmit">
                <span class="be-btn-text">Consultar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="atpvCaptchaCancel">Cancelar</button>
        </div>
    </div>
</div>
