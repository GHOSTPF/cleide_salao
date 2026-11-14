<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloqueioAgenda extends Model
{
    use RegistraAtividade, UsaTimestampTz;

    protected $table = 'bloqueios_agenda';

    protected $fillable = ['profissional_id', 'inicio', 'fim', 'motivo'];

    protected function casts(): array
    {
        return ['inicio' => 'datetime', 'fim' => 'datetime'];
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function getSalaoInteiroAttribute(): bool
    {
        return $this->profissional_id === null;
    }
}
