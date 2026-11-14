<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListaEspera extends Model
{
    use RegistraAtividade, UsaTimestampTz;

    protected $table = 'lista_espera';

    public const PERIODOS = ['manha' => 'Manhã', 'tarde' => 'Tarde', 'qualquer' => 'Qualquer horário'];

    public const STATUS = ['aguardando' => 'Aguardando', 'contatada' => 'Contatada', 'convertida' => 'Convertida', 'expirada' => 'Expirada'];

    protected $fillable = ['cliente_id', 'servico_id', 'profissional_id', 'data_desejada', 'periodo_preferido', 'status', 'agendamento_id', 'vaga_liberada_em'];

    protected function casts(): array
    {
        return ['data_desejada' => 'date', 'vaga_liberada_em' => 'datetime'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class)->withTrashed();
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }
}
