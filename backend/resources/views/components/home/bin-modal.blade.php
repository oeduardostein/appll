<div class="be-overlay hidden" id="binOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="binTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="binTitle">Pesquisa BIN</h2>
            <button class="be-dialog-close" type="button" id="binClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <div class="be-radio-group">
                <label class="be-radio-option">
                    <input type="radio" name="binSearchOption" value="placa" checked>
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Placa + Renavam</span>
                </label>
                <label class="be-radio-option">
                    <input type="radio" name="binSearchOption" value="chassi">
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Chassi</span>
                </label>
            </div>
            <div class="be-field-group" id="binPlacaGroup">
                <input
                    class="be-input"
                    id="binPlacaInput"
                    type="text"
                    placeholder="Placa"
                    maxlength="7"
                    autocomplete="off"
                >
                <input
                    class="be-input"
                    id="binRenavamInput"
                    type="text"
                    placeholder="Renavam"
                    maxlength="11"
                    autocomplete="off"
                >
            </div>
            <div class="be-field-group hidden" id="binChassiGroup">
                <input
                    class="be-input"
                    id="binChassiInput"
                    type="text"
                    placeholder="Chassi"
                    maxlength="17"
                    autocomplete="off"
                >
            </div>
            <div class="be-dialog-error" id="binError"></div>
            <button class="be-dialog-submit" type="button" id="binSubmit">
                <span class="be-btn-text">Consultar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="binCancel">Cancelar</button>
        </div>
    </div>
</div>
