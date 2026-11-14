<?php

namespace App\Models;

use App\Enums\OrigemCliente;
use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use App\Support\Telefone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, RegistraAtividade, SoftDeletes, UsaTimestampTz;

    protected $table = 'clientes';

    protected $fillable = [
        'nome', 'telefone', 'email', 'data_nascimento', 'instagram', 'observacoes', 'origem', 'consentimento_lgpd_em',
        'primeira_visita_em', 'ultima_visita_em', 'total_visitas', 'total_gasto', 'total_faltas', 'total_cancelamentos',
        'total_reagendamentos', 'bloqueada_online', 'motivo_bloqueio', 'profissional_preferida_id', 'mesclada_em_id',
    ];

    protected function casts(): array
    {
        return [
            'origem' => OrigemCliente::class,
            'data_nascimento' => 'date',
            'consentimento_lgpd_em' => 'datetime',
            'primeira_visita_em' => 'datetime',
            'ultima_visita_em' => 'datetime',
            'total_gasto' => 'decimal:2',
            'total_visitas' => 'integer',
            'total_faltas' => 'integer',
            'total_cancelamentos' => 'integer',
            'total_reagendamentos' => 'integer',
            'bloqueada_online' => 'boolean',
        ];
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function anotacoes(): HasMany
    {
        return $this->hasMany(AtendimentoAnotacao::class)->latest('created_at');
    }

    public function listaEspera(): HasMany
    {
        return $this->hasMany(ListaEspera::class);
    }

    public function profissionalPreferida(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_preferida_id');
    }

    public function mescladaEm(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'mesclada_em_id')->withTrashed();
    }

    public function getTelefoneFormatadoAttribute(): string
    {
        return Telefone::formatar($this->telefone);
    }

    public function getPrimeiroNomeAttribute(): string
    {
        return explode(' ', trim($this->nome))[0];
    }

    public function getTicketMedioAttribute(): float
    {
        return $this->total_visitas > 0 ? round((float) $this->total_gasto / $this->total_visitas, 2) : 0.0;
    }
}
