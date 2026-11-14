<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioTrabalho extends Model
{
    protected $table = 'horarios_trabalho';

    public $timestamps = false;

    protected $fillable = ['profissional_id', 'dia_semana', 'hora_inicio', 'hora_fim', 'intervalo_inicio', 'intervalo_fim'];

    protected function casts(): array
    {
        return ['dia_semana' => 'integer'];
    }

    public const DIAS = [0 => 'Domingo', 1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado'];

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }
}
