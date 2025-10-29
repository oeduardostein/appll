@props([
    'client',
    'checked' => false,
])

@php
    $isActive = strtolower($client['status']) === 'ativo';
@endphp

<tr>
    <td style="width: 52px;">
        <label style="display: inline-flex; align-items: center; justify-content: center;">
            <input
                type="checkbox"
                @checked($checked)
                style="
                    width: 18px;
                    height: 18px;
                    border-radius: 6px;
                    border: 1px solid #c0d3f3;
                    accent-color: var(--brand-primary);
                "
            />
        </label>
    </td>
    <td>
        <div style="display: flex; align-items: center; gap: 14px;">
            <span style="
                width: 46px;
                height: 46px;
                border-radius: 999px;
                overflow: hidden;
                background: #d9e5fb;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            ">
                <img
                    src="{{ $client['avatar'] ?? asset('images/logo-admin.svg') }}"
                    alt="{{ $client['name'] }}"
                    style="width: 100%; height: 100%; object-fit: cover;"
                />
            </span>
            <div>
                <strong style="display: block; font-size: 15px; color: var(--text-strong);">
                    {{ $client['name'] }}
                </strong>
                <span style="font-size: 13px; color: var(--text-muted);">
                    {{ $client['email'] }}
                </span>
            </div>
        </div>
    </td>
    <td>
        <span class="status-pill {{ $isActive ? '' : 'inactive' }}">
            {{ $client['status'] }}
        </span>
    </td>
    <td>{{ $client['credits'] }}</td>
    <td>{{ $client['last_access'] }}</td>
    <td style="width: 120px;">
        <div class="action-buttons">
            <button type="button" class="action-icon" title="Editar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="M12 20h9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor"
                        stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <button type="button" class="action-icon" title="Excluir">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="M4 7h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    <path d="M10 11v6m4-6v6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M6 7h12l-.8 11.2A2 2 0 0 1 15.21 20H8.79a2 2 0 0 1-1.99-1.8L6 7Zm3-3h6a1 1 0 0 1 1 1v2H8V5a1 1 0 0 1 1-1Z"
                        stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        </div>
    </td>
</tr>
