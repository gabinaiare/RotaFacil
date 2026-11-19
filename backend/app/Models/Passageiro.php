<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Passageiro extends Model
{
    protected $fillable = ['nome', 'cpf', 'telefone', 'email', 'endereco'];

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function passagens(): HasMany
    {
        return $this->hasMany(Passagem::class);
    }
}
