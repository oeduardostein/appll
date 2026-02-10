<div class="be-overlay hidden" id="baseQueryOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="baseQueryTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="baseQueryTitle">Consulta base estadual</h2>
            <button class="be-dialog-close" type="button" id="baseQueryClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <div class="be-radio-group">
                <label class="be-radio-option">
                    <input type="radio" name="basePlateFormat" value="antiga">
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Placa antiga (ABC-1234)</span>
                </label>
                <label class="be-radio-option">
                    <input type="radio" name="basePlateFormat" value="mercosul">
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Mercosul (ABC-1D23)</span>
                </label>
            </div>
            <input
                class="be-input"
                id="basePlateInput"
                type="text"
                placeholder="Selecione o padrão da placa"
                maxlength="8"
                autocomplete="off"
                disabled
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
