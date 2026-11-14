<?php

namespace App\Services;

use App\Enums\OrigemCliente;
use App\Models\Agendamento;
use App\Models\ListaEspera;
use App\Models\Servico;
use App\Support\Atividade;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ListaEsperaService
{
    public function __construct(
        private readonly ClienteService $clientes,
        private readonly DisponibilidadeService $disponibilidade,
    ) {}

    /** Entrada pelo site (nome, telefone, servico_id, profissional_id?, data, periodo). */
    public function adicionarPeloSite(array $dados): ListaEspera
    {
        return DB::transaction(function () use ($dados) {
            $cliente = $this->clientes->upsertPorTelefone($dados, OrigemCliente::Site, (bool) ($dados['consentimento'] ?? false))['cliente'];

            // Evita duplicar a mesma espera
            return ListaEspera::firstOrCreate([
                'cliente_id' => $cliente->id,
                'servico_id' => $dados['servico_id'],
                'data_desejada' => Carbon::parse($dados['data'])->toDateString(),
                'status' => 'aguardando',
            ], [
                'profissional_id' => $dados['profissional_id'] ?? null,
                'periodo_preferido' => $dados['periodo_preferido'] ?? 'qualquer',
            ]);
        });
    }

    /**
     * Após um cancelamento/reagendamento, marca as entradas daquele dia que agora têm horário livre.
     * Não envia mensagem: só destaca nos alertas do dashboard.
     */
    public function verificarVagaLiberada(Agendamento $liberado): int
    {
        $entradas = ListaEspera::with('servico')
            ->where('status', 'aguardando')
            ->whereDate('data_desejada', $liberado->inicio->toDateString())
            ->where(fn ($q) => $q->whereNull('profissional_id')->orWhere('profissional_id', $liberado->profissional_id))
            ->get();

        $marcadas = 0;
        foreach ($entradas as $entrada) {
            $slots = $this->disponibilidade->slots($entrada->servico, $entrada->profissional_id, $entrada->data_desejada, DisponibilidadeService::MODO_PAINEL);
            $slots = $this->filtrarPeriodo($slots, $entrada->periodo_preferido);
            if ($slots) {
                $entrada->update(['vaga_liberada_em' => now()]);
                $marcadas++;
            }
        }

        return $marcadas;
    }

    public function filtrarPeriodo(array $slots, string $periodo): array
    {
        return array_values(array_filter($slots, fn ($s) => match ($periodo) {
            'manha' => $s['hora'] < '12:00',
            'tarde' => $s['hora'] >= '12:00',
            default => true,
        }));
    }

    public function marcarContatada(ListaEspera $entrada): void
    {
        Atividade::acao('contatou', fn () => $entrada->update(['status' => 'contatada']));
    }

    public function converter(ListaEspera $entrada, Agendamento $agendamento): void
    {
        Atividade::acao('converteu', fn () => $entrada->update(['status' => 'convertida', 'agendamento_id' => $agendamento->id]));
    }

    /** Expira entradas cuja data desejada já passou. */
    public function expirar(): int
    {
        return ListaEspera::whereIn('status', ['aguardando', 'contatada'])
            ->whereDate('data_desejada', '<', today())
            ->update(['status' => 'expirada', 'updated_at' => now()]);
    }

    public function servicoDisponivelNoSite(int $servicoId): ?Servico
    {
        return Servico::noSite()->find($servicoId);
    }
}
