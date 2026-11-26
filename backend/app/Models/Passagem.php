<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /** Total já recebido (pagamentos com situação "pago") */
    protected function recebido(): Attribute
    {
        return Attribute::get(fn () => round((float) $this->pagamentos->where('situacao', 'pago')->sum('valor'), 2));
    }

    /** Quanto ainda falta receber (fiado) */
    protected function saldo(): Attribute
    {
        return Attribute::get(fn () => round((float) $this->pagamentos->where('situacao', 'pendente')->sum('valor'), 2));
    }

    protected function situacaoPagamento(): Attribute
    {
        return Attribute::get(function () {
            if ($this->saldo <= 0) {
                return 'Pago';
            }

            return $this->recebido > 0 ? 'Pago parcialmente' : 'A receber';
        });
    }
}
