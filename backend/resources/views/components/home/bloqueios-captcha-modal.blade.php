<div class="be-overlay bloqueios-captcha-overlay hidden" id="bloqueiosCaptchaOverlay" aria-hidden="true">
    <div class="bloqueios-dialog bloqueios-captcha-dialog" role="dialog" aria-modal="true" aria-labelledby="bloqueiosCaptchaTitle">
        <div class="bloqueios-dialog-header">
            <h2 class="bloqueios-dialog-title" id="bloqueiosCaptchaTitle">Digite o captcha</h2>
            <button class="be-dialog-close" type="button" id="bloqueiosCaptchaClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="bloqueios-dialog-body bloqueios-captcha-body">
            <div class="bloqueios-captcha-image-wrapper">
                <div class="be-captcha-image">
                    <img id="bloqueiosCaptchaImage" alt="Captcha">
                    <div class="be-captcha-loading hidden" id="bloqueiosCaptchaLoading">
                        <span class="spinner"></span>
                        <span>Carregando captcha...</span>
                    </div>
                </div>
                <button class="bloqueios-captcha-refresh" type="button" id="bloqueiosCaptchaRefresh">Atualizar</button>
            </div>
            <input
                class="bloqueios-chassi-input bloqueios-captcha-input"
                id="bloqueiosCaptchaInput"
                type="text"
                placeholder="Digite o captcha"
                maxlength="10"
                autocomplete="off"
            >
            <div class="bloqueios-error" id="bloqueiosCaptchaError"></div>
            <button class="be-dialog-submit bloqueios-submit-button" type="button" id="bloqueiosCaptchaSubmit">
                <span class="be-btn-text">Pesquisar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="bloqueiosCaptchaCancel">Cancelar</button>
        </div>
    </div>
</div>
