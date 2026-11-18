<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Passagem extends Model
{
    protected $table = 'passagens';

    protected $fillable = [
        'viagem_id', 'passageiro_id', 'codigo', 'assento', 'valor',
        'situacao', 'data_cancelamento', 'motivo_cancelamento',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_cancelamento' => 'datetime',
    ];

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class);
    }

    public function passageiro(): BelongsTo
    {
        return $this->belongsTo(Passageiro::class);
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }
}