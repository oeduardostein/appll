<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pesquisa extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nome',
        'placa',
        'renavam',
        'chassi',
        'opcao_pesquisa',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

