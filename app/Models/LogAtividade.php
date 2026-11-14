<?php

namespace App\Models;

use App\Models\Concerns\UsaTimestampTz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAtividade extends Model
{
    use UsaTimestampTz;

    protected $table = 'log_atividades';

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'acao', 'entidade_tipo', 'entidade_id', 'dados_antes', 'dados_depois', 'ip'];

    protected function casts(): array
    {
        return ['dados_antes' => 'array', 'dados_depois' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
