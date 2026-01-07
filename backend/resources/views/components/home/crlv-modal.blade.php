<div class="be-overlay hidden" id="crlvOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="crlvTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="crlvTitle">Emissão do CRLV-e</h2>
            <button class="be-dialog-close" type="button" id="crlvClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <p class="be-dialog-description">
                Informe apenas os dados. Resolveremos o captcha automaticamente.
            </p>
            <input
                class="be-input"
                id="crlvPlateInput"
                type="text"
                placeholder="Placa"
                maxlength="7"
                autocomplete="off"
            >
            <input
                class="be-input"
                id="crlvRenavamInput"
                type="text"
                placeholder="Renavam"
                maxlength="11"
                inputmode="numeric"
                autocomplete="off"
            >
            <input
                class="be-input"
                id="crlvDocumentInput"
                type="text"
                placeholder="CPF / CNPJ"
                maxlength="14"
                inputmode="numeric"
                autocomplete="off"
            >
            <div class="be-dialog-error" id="crlvError"></div>
            <button class="be-dialog-submit" type="button" id="crlvSubmitBtn">
                <span class="be-btn-text">Emitir</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="crlvCancelBtn">Cancelar</button>
        </div>
    </div>
</div>
