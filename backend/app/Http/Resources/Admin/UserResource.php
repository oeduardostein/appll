<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lastLogin = $this->last_login_at ?? $this->updated_at;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'initials' => $this->resolveInitials(),
            'is_active' => (bool) $this->is_active,
            'status_label' => $this->is_active ? 'Ativo' : 'Inativo',
            'credits_used' => (int) ($this->credits_used ?? $this->pesquisas_count ?? 0),
            'credits_used_label' => sprintf('%d créditos utilizados', (int) ($this->credits_used ?? $this->pesquisas_count ?? 0)),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'last_login_label' => $lastLogin ? $lastLogin->timezone(config('app.timezone'))->format('d/m/Y H:i') : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function resolveInitials(): string
    {
        $segments = collect(explode(' ', (string) $this->name))
            ->filter(static fn ($segment) => $segment !== '');

        if ($segments->isEmpty()) {
            return 'U';
        }

        return $segments
            ->map(static fn ($segment) => mb_substr($segment, 0, 1))
            ->take(2)
            ->implode('');
    }
}
