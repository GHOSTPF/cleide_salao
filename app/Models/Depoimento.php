<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use Illuminate\Database\Eloquent\Model;

class Depoimento extends Model
{
    use RegistraAtividade, UsaTimestampTz;

    protected $table = 'depoimentos';

    protected $fillable = ['nome_cliente', 'texto', 'nota', 'ativo', 'ordem'];

    protected function casts(): array
    {
        return ['nota' => 'integer', 'ativo' => 'boolean'];
    }
}
