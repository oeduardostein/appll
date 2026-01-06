<div class="be-overlay hidden" id="baseQueryOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="baseQueryTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="baseQueryTitle">Consulta base estadual</h2>
            <button class="be-dialog-close" type="button" id="baseQueryClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <input
                class="be-input"
                id="basePlateInput"
                type="text"
                placeholder="Placa"
                maxlength="7"
                autocomplete="off"
            >
            <div class="be-dialog-error" id="basePlateError"></div>
            <button class="be-dialog-submit" type="button" id="baseConsultBtn">
                <span class="be-btn-text">Consultar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="baseCancelBtn">Cancelar</button>
        </div>
    </div>
</div>
