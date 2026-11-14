<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Configurações chave/valor do salão (tabela `configuracoes`) com cache simples.
 */
class Setting
{
    private const CACHE_KEY = 'configuracoes.todas';

    public const PADROES = [
        'nome_salao' => 'Cleide Luna Studio',
        'slogan' => 'Realçando a sua beleza',
        'sobre' => 'Beleza, técnica e cuidado em cada detalhe. Especialistas em loiros e descoloração, cortes femininos, sobrancelhas, unhas e make.',
        'whatsapp_salao' => '5583986669294',
        'instagram' => 'cleidelunastudio',
        'endereco' => 'João Pessoa - PB',
        'link_maps' => 'https://maps.google.com/?q=Jo%C3%A3o+Pessoa+PB',
        'horario_funcionamento' => 'Segunda a sábado, das 9h às 18h',
        'antecedencia_minima_horas' => '2',
        'dias_maximos_agendamento' => '60',
        'intervalo_slots_minutos' => '30',
        'profissional_gerencia_bloqueios' => '1',
        'profissional_pode_reagendar' => '1',
        'profissional_pode_cancelar' => '0',
        'antecedencia_cancelamento_horas' => '12',
        'limite_reagendamentos_por_agendamento' => '2',
        'limite_agendamentos_pendentes_por_cliente' => '2',
        'faltas_para_bloqueio' => '3',
        'expirar_pendente_horas' => '0',
        'dias_cliente_inativa' => '90',
    ];

    public const BOOLEANAS = ['profissional_gerencia_bloqueios', 'profissional_pode_reagendar', 'profissional_pode_cancelar'];

    public const INTEIRAS = [
        'antecedencia_minima_horas', 'dias_maximos_agendamento', 'intervalo_slots_minutos',
        'antecedencia_cancelamento_horas', 'limite_reagendamentos_por_agendamento',
        'limite_agendamentos_pendentes_por_cliente', 'faltas_para_bloqueio', 'expirar_pendente_horas', 'dias_cliente_inativa',
    ];

    public static function todas(): array
    {
        $salvas = Cache::rememberForever(self::CACHE_KEY, function () {
            if (! Schema::hasTable('configuracoes')) {
                return [];
            }

            return DB::table('configuracoes')->pluck('valor', 'chave')->all();
        });

        return array_merge(self::PADROES, $salvas);
    }

    public static function get(string $chave, mixed $padrao = null): mixed
    {
        $valor = self::todas()[$chave] ?? $padrao;

        if (in_array($chave, self::BOOLEANAS, true)) {
            return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
        }
        if (in_array($chave, self::INTEIRAS, true)) {
            return (int) $valor;
        }

        return $valor;
    }

    public static function int(string $chave): int
    {
        return (int) self::get($chave);
    }

    public static function bool(string $chave): bool
    {
        return (bool) self::get($chave);
    }

    public static function set(string $chave, mixed $valor): void
    {
        self::setMuitas([$chave => $valor]);
    }

    public static function setMuitas(array $valores): void
    {
        foreach ($valores as $chave => $valor) {
            if (is_bool($valor)) {
                $valor = $valor ? '1' : '0';
            }
            DB::table('configuracoes')->updateOrInsert(['chave' => $chave], ['valor' => $valor]);
        }

        self::limparCache();
    }

    public static function limparCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
