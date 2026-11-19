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

#[Fillable(['name', 'email', 'password', 'cpf', 'telefone', 'login', 'cidade_estado', 'foto_perfil'])]
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
