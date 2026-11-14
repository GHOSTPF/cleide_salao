<?php

namespace App\Models;

use App\Enums\PapelUsuario;
use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, RegistraAtividade, UsaTimestampTz;

    protected $fillable = ['nome', 'email', 'password', 'papel', 'ativo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'papel' => PapelUsuario::class,
            'ativo' => 'boolean',
        ];
    }

    public function profissional(): HasOne
    {
        return $this->hasOne(Profissional::class);
    }

    public function isAdmin(): bool
    {
        return $this->papel === PapelUsuario::Admin;
    }

    public function isProfissional(): bool
    {
        return $this->papel === PapelUsuario::Profissional;
    }

    public function rotaInicial(): string
    {
        return $this->isAdmin() ? route('admin.dashboard') : route('profissional.agenda');
    }
}
