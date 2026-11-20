<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected function iniciais(): Attribute
    {
        return Attribute::get(function () {
            $partes = preg_split('/\s+/', trim($this->nome));
            $primeira = mb_substr($partes[0], 0, 1);
            $ultima = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';

            return mb_strtoupper($primeira . $ultima);
        });
    }

    protected function telefoneFormatado(): Attribute
    {
        return Attribute::get(function () {
            $d = preg_replace('/\D/', '', (string) $this->telefone);

            if (strlen($d) === 11) {
                return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7));
            }
            if (strlen($d) === 10) {
                return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6));
            }

            return $this->telefone;
        });
    }

    protected function cpfFormatado(): Attribute
    {
        return Attribute::get(function () {
            $d = preg_replace('/\D/', '', (string) $this->cpf);

            return strlen($d) === 11
                ? sprintf('%s.%s.%s-%s', substr($d, 0, 3), substr($d, 3, 3), substr($d, 6, 3), substr($d, 9))
                : null;
        });
    }
}
