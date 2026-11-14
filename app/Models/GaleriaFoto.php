<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GaleriaFoto extends Model
{
    use RegistraAtividade, UsaTimestampTz;

    protected $table = 'galeria_fotos';

    /** Espelham os destaques do Instagram. */
    public const CATEGORIAS = [
        'loiros' => 'Loiros', 'cortes' => 'Cortes', 'unhas' => 'Nails', 'maquiagem' => 'Make',
        'sobrancelhas' => 'Sobrancelhas', 'ambiente' => 'Ambiente',
    ];

    protected $fillable = ['titulo', 'categoria', 'caminho', 'destaque', 'ordem'];

    protected function casts(): array
    {
        return ['destaque' => 'boolean'];
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->caminho, 'http') || str_starts_with($this->caminho, '/')) {
            return $this->caminho;
        }

        return Storage::disk('public')->url($this->caminho);
    }
}
