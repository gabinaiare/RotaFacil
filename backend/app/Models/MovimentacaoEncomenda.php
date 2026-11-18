<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoEncomenda extends Model
{
    protected $table = 'movimentacoes_encomenda';

    protected $fillable = ['encomenda_id', 'situacao_anterior', 'nova_situacao'];

    public function encomenda(): BelongsTo
    {
        return $this->belongsTo(Encomenda::class);
    }
}