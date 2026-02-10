<div class="be-overlay hidden" id="gravameOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="gravameTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="gravameTitle">Consultar gravame</h2>
            <button class="be-dialog-close" type="button" id="gravameClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <p class="be-dialog-helper">
                Pesquise pela placa ou pelo chassi. Resolveremos o captcha automaticamente.
            </p>
            <div class="be-radio-group">
                <label class="be-radio-option">
                    <input type="radio" name="gravameSearchOption" value="placa" checked>
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Placa</span>
                </label>
                <label class="be-radio-option">
                    <input type="radio" name="gravameSearchOption" value="chassi">
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Chassi</span>
                </label>
            </div>
            <div class="be-field-group" id="gravamePlacaGroup">
                <div class="be-radio-group">
                    <label class="be-radio-option">
                        <input type="radio" name="gravamePlateFormat" value="antiga">
                        <span class="be-radio-mark"></span>
                        <span class="be-radio-text">Antiga (ABC-1234)</span>
                    </label>
                    <label class="be-radio-option">
                        <input type="radio" name="gravamePlateFormat" value="mercosul">
                        <span class="be-radio-mark"></span>
                        <span class="be-radio-text">Mercosul (ABC-1D23)</span>
                    </label>
                </div>
                <input
                    class="be-input"
                    id="gravamePlateInput"
                    type="text"
                    placeholder="Selecione o padrão da placa"
                    maxlength="8"
                    autocomplete="off"
                    disabled
                >
            </div>
            <div class="be-field-group hidden" id="gravameChassiGroup">
                <input
                    class="be-input"
                    id="gravameChassiInput"
                    type="text"
                    placeholder="Chassi"
                    maxlength="17"
                    autocomplete="off"
                >
            </div>
            <div class="be-dialog-error" id="gravameError"></div>
            <button class="be-dialog-submit" type="button" id="gravameSubmit">
                <span class="be-btn-text">Consultar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="gravameCancel">Cancelar</button>
        </div>
    </div>
</div>
