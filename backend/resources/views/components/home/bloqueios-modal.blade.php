<div class="be-overlay bloqueios-overlay hidden" id="bloqueiosOverlay" aria-hidden="true">
    <div class="bloqueios-dialog" role="dialog" aria-modal="true" aria-labelledby="bloqueiosTitle">
        <div class="bloqueios-dialog-header">
            <h2 class="bloqueios-dialog-title" id="bloqueiosTitle">Bloqueios ativos</h2>
            <button class="be-dialog-close" type="button" id="bloqueiosClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="bloqueios-dialog-body">
            <div class="bloqueios-source-toggle" role="group" aria-label="Origem da consulta">
                <button class="bloqueios-source-button active" type="button" data-source="DETRAN">DETRAN</button>
                <button class="bloqueios-source-button" type="button" data-source="RENAJUD">RENAJUD</button>
            </div>
            <input
                class="bloqueios-chassi-input"
                id="bloqueiosChassiInput"
                type="text"
                placeholder="Chassi"
                maxlength="17"
                autocomplete="off"
            >
            <div class="bloqueios-error" id="bloqueiosError"></div>
            <button class="be-dialog-submit bloqueios-submit-button" type="button" id="bloqueiosSearchBtn">
                <span class="be-btn-text">Pesquisar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="bloqueiosCancelBtn">Cancelar</button>
        </div>
    </div>
</div>
