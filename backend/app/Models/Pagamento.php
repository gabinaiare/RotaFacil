<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pagamento extends Model
{
    protected $fillable = [
        'passagem_id', 'encomenda_id', 'forma', 'situacao', 'valor', 'data_pagamento',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_pagamento' => 'datetime',
    ];

    public function passagem(): BelongsTo
    {
        return $this->belongsTo(Passagem::class);
    }

    public function encomenda(): BelongsTo
    {
        return $this->belongsTo(Encomenda::class);
    }
}