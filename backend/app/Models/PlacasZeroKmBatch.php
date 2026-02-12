<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlacasZeroKmBatch extends Model
{
    protected $table = 'placas_zero_km_batches';

    protected $fillable = [
        'status',
        'total',
        'processed',
        'succeeded',
        'failed',
        'source',
        'request_ip',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(PlacasZeroKmRequest::class, 'batch_id');
    }
}

