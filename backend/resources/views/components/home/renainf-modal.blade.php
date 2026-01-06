<div class="be-overlay hidden" id="renainfOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="renainfTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="renainfTitle">Consulta RENAINF</h2>
            <button class="be-dialog-close" type="button" id="renainfClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <p class="be-dialog-helper">Informe placa, status, UF e período. Resolveremos o captcha automaticamente.</p>
            <input
                class="be-input"
                id="renainfPlateInput"
                type="text"
                placeholder="Placa"
                maxlength="7"
                autocomplete="off"
            >
            <select class="be-input be-select" id="renainfStatusSelect">
                <option value="2">Todas</option>
                <option value="1">Multas em cobrança</option>
            </select>
            <select class="be-input be-select" id="renainfUfSelect">
                <option value="">Selecione a UF</option>
                <option value="AC">AC</option>
                <option value="AL">AL</option>
                <option value="AP">AP</option>
                <option value="AM">AM</option>
                <option value="BA">BA</option>
                <option value="CE">CE</option>
                <option value="DF">DF</option>
                <option value="ES">ES</option>
                <option value="GO">GO</option>
                <option value="MA">MA</option>
                <option value="MT">MT</option>
                <option value="MS">MS</option>
                <option value="MG">MG</option>
                <option value="PA">PA</option>
                <option value="PB">PB</option>
                <option value="PR">PR</option>
                <option value="PE">PE</option>
                <option value="PI">PI</option>
                <option value="RJ">RJ</option>
                <option value="RN">RN</option>
                <option value="RS">RS</option>
                <option value="RO">RO</option>
                <option value="RR">RR</option>
                <option value="SC">SC</option>
                <option value="SP">SP</option>
                <option value="SE">SE</option>
                <option value="TO">TO</option>
            </select>
            <div class="be-field-group">
                <input
                    class="be-input"
                    id="renainfStartDate"
                    type="date"
                    placeholder="Data inicial"
                >
                <input
                    class="be-input"
                    id="renainfEndDate"
                    type="date"
                    placeholder="Data final"
                >
            </div>
            <div class="be-dialog-error" id="renainfError"></div>
            <button class="be-dialog-submit" type="button" id="renainfSubmit">
                <span class="be-btn-text">Pesquisar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="renainfCancel">Cancelar</button>
        </div>
    </div>
</div>
