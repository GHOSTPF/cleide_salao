<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtendimentoAnotacao extends Model
{
    use RegistraAtividade, UsaTimestampTz;

    protected $table = 'atendimento_anotacoes';

    public const UPDATED_AT = null;

    protected $fillable = ['cliente_id', 'agendamento_id', 'profissional_id', 'anotacao'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class)->withTrashed();
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }
}
