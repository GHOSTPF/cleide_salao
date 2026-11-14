<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\UsaTimestampTz;
use App\Support\Dinheiro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Servico extends Model
{
    use HasFactory, RegistraAtividade, UsaTimestampTz;

    protected $table = 'servicos';

    protected $fillable = [
        'categoria_servico_id', 'nome', 'slug', 'descricao', 'duracao_minutos', 'intervalo_apos_minutos', 'preco',
        'preco_a_partir_de', 'retorno_sugerido_dias', 'requer_avaliacao', 'foto', 'ativo', 'exibir_no_site', 'ordem',
    ];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'preco_a_partir_de' => 'boolean',
            'requer_avaliacao' => 'boolean',
            'ativo' => 'boolean',
            'exibir_no_site' => 'boolean',
            'duracao_minutos' => 'integer',
            'intervalo_apos_minutos' => 'integer',
            'retorno_sugerido_dias' => 'integer',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaServico::class, 'categoria_servico_id');
    }

    public function profissionais(): BelongsToMany
    {
        return $this->belongsToMany(Profissional::class, 'profissional_servico')->withPivot(['duracao_minutos', 'preco']);
    }

    public function scopeAtivos(Builder $q): Builder
    {
        return $q->where('ativo', true);
    }

    public function scopeNoSite(Builder $q): Builder
    {
        return $q->where('ativo', true)->where('exibir_no_site', true);
    }

    public function getPrecoFormatadoAttribute(): string
    {
        return ($this->preco_a_partir_de ? 'a partir de ' : '') . Dinheiro::formatar($this->preco);
    }

    public function getDuracaoFormatadaAttribute(): string
    {
        return self::formatarDuracao($this->duracao_minutos);
    }

    public static function formatarDuracao(int $min): string
    {
        $h = intdiv($min, 60);
        $m = $min % 60;
        if ($h && $m) {
            return "{$h}h{$m}";
        }

        return $h ? "{$h}h" : "{$m} min";
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }
}
