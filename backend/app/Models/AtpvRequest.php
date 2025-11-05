<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtpvRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'renavam',
        'placa',
        'chassi',
        'hodometro',
        'email_proprietario',
        'cpf_cnpj_proprietario',
        'cpf_cnpj_comprador',
        'nome_comprador',
        'email_comprador',
        'uf',
        'valor_venda',
        'cep_comprador',
        'municipio_comprador',
        'bairro_comprador',
        'logradouro_comprador',
        'numero_comprador',
        'complemento_comprador',
        'status',
        'response_payload',
        'response_errors',
    ];

    protected $casts = [
        'response_payload' => 'array',
        'response_errors' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
