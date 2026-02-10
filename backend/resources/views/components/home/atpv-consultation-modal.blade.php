<div class="be-overlay hidden" id="atpvConsultationOverlay" aria-hidden="true">
    <div class="be-dialog atpv-dialog" role="dialog" aria-modal="true" aria-labelledby="atpvConsultationTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="atpvConsultationTitle">Consultar intenção de venda</h2>
            <button class="be-dialog-close" type="button" id="atpvConsultationClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <div class="be-radio-group">
                <label class="be-radio-option">
                    <input type="radio" name="atpvPlateFormat" value="antiga">
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Placa antiga (ABC-1234)</span>
                </label>
                <label class="be-radio-option">
                    <input type="radio" name="atpvPlateFormat" value="mercosul">
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Mercosul (ABC-1D23)</span>
                </label>
            </div>
            <input
                class="be-input"
                id="atpvPlateInput"
                type="text"
                placeholder="Selecione o padrão da placa"
                maxlength="8"
                autocomplete="off"
                disabled
            >
            <input
                class="be-input"
                id="atpvRenavamInput"
                type="text"
                placeholder="Renavam"
                maxlength="11"
                inputmode="numeric"
                autocomplete="off"
            >
            <p class="atpv-dialog-helper">
                Resolveremos o captcha automaticamente após enviar os dados.
            </p>
            <div class="be-dialog-error" id="atpvConsultError"></div>
            <button class="be-dialog-submit" type="button" id="atpvConsultSubmit">
                <span class="be-btn-text">Consultar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="atpvConsultCancel">Cancelar</button>
        </div>
    </div>
</div>
