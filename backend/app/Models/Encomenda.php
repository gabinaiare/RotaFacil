<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Encomenda extends Model
{
    // A ordem importa: a situação só pode avançar
    public const SITUACOES = [
        'cadastrada' => 'Cadastrada',
        'em_transporte' => 'Em transporte',
        'disponivel_retirada' => 'Disponível para retirada',
        'entregue' => 'Entregue',
    ];

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

    protected function situacaoRotulo(): Attribute
    {
        return Attribute::get(fn () => self::SITUACOES[$this->situacao] ?? $this->situacao);
    }

    /** Total já recebido */
    protected function recebido(): Attribute
    {
        return Attribute::get(fn () => round((float) $this->pagamentos->where('situacao', 'pago')->sum('valor'), 2));
    }

    /** Quanto ainda falta receber */
    protected function saldo(): Attribute
    {
        return Attribute::get(fn () => max(0, round((float) ($this->valor ?? 0) - $this->recebido, 2)));
    }

    protected function pagamentoRotulo(): Attribute
    {
        return Attribute::get(function () {
            if ((float) ($this->valor ?? 0) <= 0) {
                return 'Sem valor';
            }
            if ($this->saldo <= 0) {
                return 'Pago';
            }

            return $this->recebido > 0 ? 'Pago parcialmente' : 'A receber';
        });
    }

    protected function remetenteTelefoneFormatado(): Attribute
    {
        return Attribute::get(fn () => self::formatarTelefone($this->remetente_telefone));
    }

    protected function destinatarioTelefoneFormatado(): Attribute
    {
        return Attribute::get(fn () => self::formatarTelefone($this->destinatario_telefone));
    }

    private static function formatarTelefone(?string $telefone): string
    {
        $d = preg_replace('/\D/', '', (string) $telefone);

        return match (strlen($d)) {
            11 => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7)),
            10 => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6)),
            default => (string) $telefone,
        };
    }
}
