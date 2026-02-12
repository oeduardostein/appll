<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlacasZeroKmRunnerState extends Model
{
    protected $table = 'placas_zero_km_runner_state';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'current_request_id',
        'is_running',
        'last_heartbeat_at',
    ];

    protected $casts = [
        'is_running' => 'integer',
        'last_heartbeat_at' => 'datetime',
    ];
}

