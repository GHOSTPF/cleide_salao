<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comissao extends Model
{
    use RegistraAtividade, UsaTimestampTz;

    protected $table = 'comissoes';

    protected $fillable = ['agendamento_id', 'profissional_id', 'base_calculo', 'percentual', 'valor', 'status', 'pago_em'];

    protected function casts(): array
    {
        return [
            'base_calculo' => 'decimal:2',
            'percentual' => 'decimal:2',
            'valor' => 'decimal:2',
            'pago_em' => 'datetime',
        ];
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
