<div class="header">
    <div class="header-inner">
        <div class="header-top">
            <div class="brand-avatar">
                <img src="{{ request()->getBaseUrl() }}/images/logoll.png" alt="LL Despachante">
            </div>
            <div class="header-actions">
                <button class="icon-button" id="profileBtn" title="Meu perfil">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </button>
                <button class="btn-outline" id="logoutBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Sair</span>
                </button>
            </div>
        </div>
        <div class="header-info" id="userInfo">Usuário: -- • Créditos usados este mês: --</div>
    </div>
</div>
