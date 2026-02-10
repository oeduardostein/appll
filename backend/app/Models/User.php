<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ApiToken;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'credits',
        'last_login_at',
        'codigo',
        'privacy_policy_accepted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'credits' => 'integer',
            'last_login_at' => 'datetime',
            'privacy_policy_accepted_at' => 'datetime',
            'login_security_key_expires_at' => 'datetime',
            'login_security_key_sent_at' => 'datetime',
        ];
    }

    /**
     * Histórico de pesquisas de bloqueios ativos vinculadas ao usuário.
     */
    public function pesquisas(): HasMany
    {
        return $this->hasMany(Pesquisa::class);
    }

    /**
     * Solicitações de emissão de ATPV vinculadas ao usuário.
     */
    public function atpvRequests(): HasMany
    {
        return $this->hasMany(AtpvRequest::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Permissões concedidas ao usuário para acessar telas específicas.
     */
    public function permissions(): BelongsToMany
    {
        return $this
            ->belongsToMany(Permission::class, 'user_permissions')
            ->withTimestamps()
            ->withPivot(['credit_value']);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public static function findByApiToken(string $token, array $with = []): ?self
    {
        $hash = hash('sha256', $token);

        $query = static::query()
            ->whereHas('apiTokens', function ($query) use ($hash) {
                $query->where('token', $hash);
            });

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->first();
    }
}
