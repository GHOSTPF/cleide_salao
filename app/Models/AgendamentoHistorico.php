<?php

namespace App\Models;

use App\Models\Concerns\UsaTimestampTz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendamentoHistorico extends Model
{
    use UsaTimestampTz;

    protected $table = 'agendamento_historico';

    public const UPDATED_AT = null;

    protected $fillable = ['agendamento_id', 'user_id', 'status_anterior', 'status_novo', 'observacao'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
