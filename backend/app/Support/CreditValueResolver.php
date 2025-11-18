<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Str;

class CreditValueResolver
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->user->loadMissing(['permissions' => function ($query): void {
            $query->select('permissions.id', 'permissions.name', 'permissions.slug', 'permissions.default_credit_value');
        }]);
    }

    public static function forUser(User $user): self
    {
        return new self($user);
    }

    public function resolveForPesquisa(string $nome): float
    {
        $slug = self::slugFromPesquisaNome($nome);

        if ($slug !== null) {
            return $this->resolveBySlug($slug);
        }

        return $this->fallbackValue();
    }

    public function resolveBySlug(string $slug): float
    {
        $permission = $this->user->permissions
            ->firstWhere('slug', $slug);

        if ($permission) {
            $value = $permission->pivot?->credit_value ?? $permission->default_credit_value;
            if ($value !== null) {
                return (float) $value;
            }
        }

        $defaults = config('credit-values.services', []);
        if (array_key_exists($slug, $defaults)) {
            return (float) $defaults[$slug];
        }

        $existing = Permission::query()
            ->where('slug', $slug)
            ->value('default_credit_value');

        if ($existing !== null) {
            return (float) $existing;
        }

        if (str_starts_with($slug, 'pesquisa_')) {
            return 1.0;
        }

        return $this->fallbackValue();
    }

    public static function slugFromPesquisaNome(string $nome): ?string
    {
        $normalized = self::normalize($nome);
        $map = self::nomeToSlugMap();

        return $map[$normalized] ?? null;
    }

    private static function nomeToSlugMap(): array
    {
        return [
            'base estadual' => 'pesquisa_base_estadual',
            'base outros estados' => 'pesquisa_base_outros_estados',
            'bin' => 'pesquisa_bin',
            'gravame' => 'pesquisa_gravame',
            'renainf' => 'pesquisa_renainf',
            'bloqueios ativos' => 'pesquisa_bloqueios_ativos',
            'processo e-crvsp' => 'pesquisa_andamento_processo',
            'emissao do crlv-e' => 'crlv',
        ];
    }

    private static function normalize(string $value): string
    {
        return trim((string) Str::of($value)->lower()->ascii());
    }

    private function fallbackValue(): float
    {
        return (float) (config('credit-values.fallback') ?? 1.0);
    }
}
