<div class="be-overlay hidden" id="atpvConsultationOverlay" aria-hidden="true">
    <div class="be-dialog atpv-dialog" role="dialog" aria-modal="true" aria-labelledby="atpvConsultationTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="atpvConsultationTitle">Consultar intenção de venda</h2>
            <button class="be-dialog-close" type="button" id="atpvConsultationClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <input
                class="be-input"
                id="atpvPlateInput"
                type="text"
                placeholder="Placa"
                maxlength="7"
                autocomplete="off"
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
