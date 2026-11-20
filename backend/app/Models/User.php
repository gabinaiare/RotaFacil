<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'cpf', 'telefone', 'login', 'cidade_estado', 'numero_cnh', 'categoria_cnh', 'foto'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function iniciais(): Attribute
    {
        return Attribute::get(function () {
            $partes = preg_split('/\s+/', trim($this->name));
            $primeira = mb_substr($partes[0], 0, 1);
            $ultima = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';

            return mb_strtoupper($primeira . $ultima);
        });
    }

    protected function primeiroNome(): Attribute
    {
        return Attribute::get(fn () => explode(' ', trim($this->name))[0]);
    }

    protected function fotoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->foto ? asset('storage/' . $this->foto) : null);
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

    protected function telefoneFormatado(): Attribute
    {
        return Attribute::get(function () {
            $d = preg_replace('/\D/', '', (string) $this->telefone);

            return match (strlen($d)) {
                11 => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7)),
                10 => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6)),
                default => $this->telefone,
            };
        });
    }

    public function veiculos(): HasMany
    {
        return $this->hasMany(Veiculo::class);
    }

    public function passageiros(): HasMany
    {
        return $this->hasMany(Passageiro::class);
    }

    public function viagens(): HasMany
    {
        return $this->hasMany(Viagem::class);
    }

    public function encomendas(): HasMany
    {
        return $this->hasMany(Encomenda::class);
    }
}
