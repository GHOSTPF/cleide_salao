<?php

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pagamento extends Model
{
    use RegistraAtividade, UsaTimestampTz;

    protected $table = 'pagamentos';

    protected $fillable = ['agendamento_id', 'valor', 'forma', 'pago_em', 'registrado_por_user_id'];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'forma' => FormaPagamento::class,
            'pago_em' => 'datetime',
        ];
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }
}
