<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use Illuminate\Database\Eloquent\Model;

class Despesa extends Model
{
    use RegistraAtividade, UsaTimestampTz;

    protected $table = 'despesas';

    public const CATEGORIAS = [
        'aluguel' => 'Aluguel', 'produtos' => 'Produtos', 'energia' => 'Energia', 'agua' => 'Água',
        'internet' => 'Internet', 'marketing' => 'Marketing', 'outros' => 'Outros',
    ];

    protected $fillable = ['descricao', 'categoria', 'valor', 'data', 'pago', 'observacao'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:2', 'data' => 'date', 'pago' => 'boolean'];
    }
}
