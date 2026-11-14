<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Profissional extends Model
{
    use HasFactory, RegistraAtividade, UsaTimestampTz;

    protected $table = 'profissionais';

    protected $fillable = ['user_id', 'nome', 'apelido', 'telefone', 'foto', 'bio', 'cor_agenda', 'percentual_comissao', 'ativo', 'ordem'];

    protected function casts(): array
    {
        return [
            'percentual_comissao' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function servicos(): BelongsToMany
    {
        return $this->belongsToMany(Servico::class, 'profissional_servico')->withPivot(['duracao_minutos', 'preco']);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioTrabalho::class)->orderBy('dia_semana');
    }

    public function bloqueios(): HasMany
    {
        return $this->hasMany(BloqueioAgenda::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function comissoes(): HasMany
    {
        return $this->hasMany(Comissao::class);
    }

    public function scopeAtivas(Builder $q): Builder
    {
        return $q->where('ativo', true)->orderBy('ordem')->orderBy('nome');
    }

    public function getNomeExibicaoAttribute(): string
    {
        return $this->apelido ?: $this->nome;
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }
}
