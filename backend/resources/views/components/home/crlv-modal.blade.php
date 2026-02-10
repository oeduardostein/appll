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
            <div class="be-radio-group">
                <label class="be-radio-option">
                    <input type="radio" name="crlvPlateFormat" value="antiga">
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Placa antiga (ABC-1234)</span>
                </label>
                <label class="be-radio-option">
                    <input type="radio" name="crlvPlateFormat" value="mercosul">
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Mercosul (ABC-1D23)</span>
                </label>
            </div>
            <input
                class="be-input"
                id="crlvPlateInput"
                type="text"
                placeholder="Selecione o padrão da placa"
                maxlength="8"
                autocomplete="off"
                disabled
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
