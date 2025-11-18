<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'default_credit_value',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'default_credit_value' => 'decimal:2',
    ];

    /**
     * Usuários que possuem esta permissão.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')->withTimestamps();
    }
}
