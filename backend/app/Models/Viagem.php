<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Viagem extends Model
{
    protected $table = 'viagens';

    protected $fillable = [
        'veiculo_id', 'codigo', 'origem', 'destino',
        'saida', 'valor', 'capacidade', 'situacao',
    ];

    protected $casts = [
        'saida' => 'datetime',
        'valor' => 'decimal:2',
    ];

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function passagens(): HasMany
    {
        return $this->hasMany(Passagem::class);
    }

    public function encomendas(): HasMany
    {
        return $this->hasMany(Encomenda::class);
    }
}