<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Veiculo extends Model
{
    protected $fillable = ['identificacao', 'placa', 'modelo', 'tipo', 'capacidade', 'situacao'];

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function viagens(): HasMany
    {
        return $this->hasMany(Viagem::class);
    }
}