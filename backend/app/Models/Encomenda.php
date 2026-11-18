<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Encomenda extends Model
{
    protected $fillable = [
        'viagem_id', 'codigo',
        'remetente_nome', 'remetente_telefone',
        'destinatario_nome', 'destinatario_telefone',
        'descricao', 'origem', 'destino', 'valor', 'situacao', 'data_entrega',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_entrega' => 'datetime',
    ];

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class);
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoEncomenda::class);
    }
}