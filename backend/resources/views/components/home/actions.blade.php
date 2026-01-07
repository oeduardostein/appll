<section class="actions-section">
    <div class="loading" id="permissionsLoading">
        <div class="spinner"></div>
        <span>Carregando permissões...</span>
    </div>
    <div class="status-message error hidden" id="permissionsError">
        <span id="permissionsErrorText">Não foi possível carregar as permissões.</span>
        <button class="text-link-button" id="permissionsRetry" type="button">Tentar novamente</button>
    </div>
    <div class="status-message hidden" id="noActionsMessage">
        Nenhuma funcionalidade liberada para este usuário.
    </div>
    <div class="actions-list" id="actionsList">
        <div class="action-card" data-action="pesquisas">
            <button class="action-card__main" type="button" data-toggle="pesquisas" aria-expanded="false">
                <div class="action-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </div>
                <div>
                    <div class="action-card__title">Pesquisas</div>
                    <div class="action-card__description">
                        Base estadual, BIN, outros Estados, RENAINF, Gravame e bloqueios ativos.
                    </div>
                </div>
                <div class="action-card__chevron">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </div>
            </button>
            <div class="action-card__sublist">
                <button class="action-subitem" type="button" data-permission="pesquisa_base_estadual" data-action="base-estadual">
                    <div class="action-subitem__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 6-9 12-9 12S3 16 3 10a9 9 0 1 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div class="action-subitem__label">Base estadual</div>
                    <div class="action-subitem__chevron">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </button>
                <button class="action-subitem" type="button" data-permission="pesquisa_base_outros_estados" data-action="base-outros-estados">
                    <div class="action-subitem__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M2 12h20"></path>
                            <path d="M12 2a15 15 0 0 1 0 20"></path>
                        </svg>
                    </div>
                    <div class="action-subitem__label">Base Outros Estados</div>
                    <div class="action-subitem__chevron">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </button>
                <button class="action-subitem" type="button" data-permission="pesquisa_bin" data-action="bin">
                    <div class="action-subitem__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                            <line x1="2" y1="10" x2="22" y2="10"></line>
                        </svg>
                    </div>
                    <div class="action-subitem__label">BIN</div>
                    <div class="action-subitem__chevron">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </button>
                <button class="action-subitem" type="button" data-permission="pesquisa_gravame" data-action="gravame">
                    <div class="action-subitem__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="14" rx="2"></rect>
                            <path d="M8 20h8"></path>
                        </svg>
                    </div>
                    <div class="action-subitem__label">Gravame</div>
                    <div class="action-subitem__chevron">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </button>
                <button class="action-subitem" type="button" data-permission="pesquisa_renainf" data-action="renainf">
                    <div class="action-subitem__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path>
                            <path d="M7 17l-1 4h12l-1-4"></path>
                            <path d="M8 11h8"></path>
                        </svg>
                    </div>
                    <div class="action-subitem__label">Renainf</div>
                    <div class="action-subitem__chevron">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </button>
                <button class="action-subitem" type="button" data-permission="pesquisa_bloqueios_ativos" data-action="bloqueios-ativos">
                    <div class="action-subitem__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <div class="action-subitem__label">Bloqueios Ativos</div>
                    <div class="action-subitem__chevron">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </button>
                <button class="action-subitem" type="button" data-permission="pesquisa_andamento_processo" data-action="andamento-ecrv">
                    <div class="action-subitem__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3v18h18"></path>
                            <path d="m19 9-5 5-4-4-4 4"></path>
                        </svg>
                    </div>
                    <div class="action-subitem__label">Andamento do processo e-CRV</div>
                    <div class="action-subitem__chevron">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </button>
            </div>
        </div>

        <div class="action-card" data-permission="crlv">
            <button class="action-card__main" type="button" data-action="crlv">
                <div class="action-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <path d="M14 2v6h6"></path>
                        <line x1="8" y1="13" x2="16" y2="13"></line>
                    </svg>
                </div>
                <div>
                    <div class="action-card__title">CRLV-e</div>
                    <div class="action-card__description">Emissão do CRLV digital</div>
                </div>
            </button>
        </div>

        <div class="action-card" data-permission="atpv">
            <button class="action-card__main" type="button" data-disabled="true">
                <div class="action-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        <path d="M9 2h6"></path>
                        <path d="m9 14 2 2 4-4"></path>
                    </svg>
                </div>
                <div>
                    <div class="action-card__title">Emissão da ATPV-e</div>
                    <div class="action-card__description">Preencher a autorização para transferência</div>
                </div>
            </button>
        </div>
    </div>
</section>
