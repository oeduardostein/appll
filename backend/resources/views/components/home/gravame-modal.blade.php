<div class="be-overlay hidden" id="gravameOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="gravameTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="gravameTitle">Consultar gravame</h2>
            <button class="be-dialog-close" type="button" id="gravameClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <p class="be-dialog-helper">
                Informe apenas a placa. Resolveremos o captcha automaticamente.
            </p>
            <input
                class="be-input"
                id="gravamePlateInput"
                type="text"
                placeholder="Placa"
                maxlength="7"
                autocomplete="off"
            >
            <div class="be-dialog-error" id="gravameError"></div>
            <button class="be-dialog-submit" type="button" id="gravameSubmit">
                <span class="be-btn-text">Consultar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="gravameCancel">Cancelar</button>
        </div>
    </div>
</div>
