<?php

namespace App\Models;

use App\Enums\CanceladoPor;
use App\Enums\OrigemAgendamento;
use App\Enums\StatusAgendamento;
use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agendamento extends Model
{
    use HasFactory, RegistraAtividade, UsaTimestampTz;

    protected $table = 'agendamentos';

    protected $guarded = ['id', 'periodo'];

    protected $hidden = ['periodo'];

    protected function casts(): array
    {
        return [
            'status' => StatusAgendamento::class,
            'origem' => OrigemAgendamento::class,
            'cancelado_por' => CanceladoPor::class,
            'inicio' => 'datetime',
            'fim' => 'datetime',
            'fim_bloqueio' => 'datetime',
            'confirmado_em' => 'datetime',
            'concluido_em' => 'datetime',
            'cancelado_em' => 'datetime',
            'whatsapp_enviado_em' => 'datetime',
            'preco_previsto' => 'decimal:2',
            'desconto' => 'decimal:2',
            'valor_cobrado' => 'decimal:2',
            'nome_divergente' => 'boolean',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class)->withTrashed();
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function historico(): HasMany
    {
        return $this->hasMany(AgendamentoHistorico::class)->orderBy('created_at')->orderBy('id');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }

    public function comissao(): HasOne
    {
        return $this->hasOne(Comissao::class);
    }

    public function anotacoes(): HasMany
    {
        return $this->hasMany(AtendimentoAnotacao::class);
    }

    public function reagendadoDe(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class, 'reagendado_de_id');
    }

    public function reagendadoPara(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class, 'reagendado_para_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por_user_id');
    }

    public function scopeAtivos(Builder $q): Builder
    {
        return $q->whereIn('status', StatusAgendamento::ativos());
    }

    public function scopeDoDia(Builder $q, $data): Builder
    {
        $d = \Illuminate\Support\Carbon::parse($data);

        return $q->where('inicio', '>=', $d->copy()->startOfDay())->where('inicio', '<', $d->copy()->addDay()->startOfDay());
    }

    public function getCodigoBaseAttribute(): string
    {
        return preg_replace('/-R\d+$/', '', $this->codigo);
    }

    /** Já passou do horário de início (necessário para concluir/registrar falta). */
    public function getJaComecouAttribute(): bool
    {
        return $this->inicio->lte(now());
    }

    public function getDuracaoMinutosAttribute(): int
    {
        return (int) $this->inicio->diffInMinutes($this->fim);
    }
}
