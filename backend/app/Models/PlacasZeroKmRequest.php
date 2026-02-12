<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacasZeroKmRequest extends Model
{
    protected $table = 'placas_zero_km_requests';

    protected $fillable = [
        'batch_id',
        'cpf_cgc',
        'nome',
        'chassi',
        'numeros',
        'status',
        'attempts',
        'response_payload',
        'response_error',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'response_payload' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PlacasZeroKmBatch::class, 'batch_id');
    }
}

