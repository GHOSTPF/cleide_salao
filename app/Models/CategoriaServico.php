<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaServico extends Model
{
    use RegistraAtividade;

    protected $table = 'categorias_servico';

    public $timestamps = false;

    protected $fillable = ['nome', 'ordem'];

    public function servicos(): HasMany
    {
        return $this->hasMany(Servico::class)->orderBy('ordem')->orderBy('nome');
    }
}
