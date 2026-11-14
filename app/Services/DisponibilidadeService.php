<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\BloqueioAgenda;
use App\Models\Profissional;
use App\Models\Servico;
use App\Support\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Motor de disponibilidade. A validação aqui é UX: a garantia final contra conflito
 * é a exclusion constraint `agendamentos_sem_conflito` no PostgreSQL.
 */
class DisponibilidadeService
{
    /** Site: respeita antecedência mínima e janela máxima de dias. */
    public const MODO_SITE = 'site';

    /** Painel: só impede horários já passados. */
    public const MODO_PAINEL = 'painel';

    /** Profissionais ativas que realizam o serviço (com pivot e horários carregados). */
    public function profissionaisAptas(Servico $servico, ?int $profissionalId = null): Collection
    {
        return $servico->profissionais()
            ->where('profissionais.ativo', true)
            ->when($profissionalId, fn ($q) => $q->where('profissionais.id', $profissionalId))
            ->with('horarios')
            ->orderBy('profissionais.ordem')
            ->orderBy('profissionais.nome')
            ->get();
    }

    /** Duração e preço considerando a sobrescrita do pivot profissional_servico. */
    public function infoServico(Servico $servico, Profissional $profissional): array
    {
        $pivot = $profissional->relationLoaded('pivot') && (int) $profissional->pivot->servico_id === $servico->id
            ? $profissional->pivot
            : DB::table('profissional_servico')->where('profissional_id', $profissional->id)->where('servico_id', $servico->id)->first();

        return [
            'duracao' => (int) ($pivot?->duracao_minutos ?: $servico->duracao_minutos),
            'preco' => (float) ($pivot?->preco ?? $servico->preco),
        ];
    }

    public function profissionalRealizaServico(Profissional $profissional, Servico $servico): bool
    {
        return DB::table('profissional_servico')->where('profissional_id', $profissional->id)->where('servico_id', $servico->id)->exists();
    }

    /**
     * Slots disponíveis no dia: [['hora' => '09:00', 'profissionais' => [1, 3]], ...]
     * Com $profissionalId = null ("Sem preferência"), retorna a união das profissionais aptas.
     */
    public function slots(Servico $servico, ?int $profissionalId, Carbon|string $data, string $modo = self::MODO_SITE, ?int $ignorarAgendamentoId = null): array
    {
        $dia = Carbon::parse($data)->startOfDay();

        if ($modo === self::MODO_SITE && ! $this->dataPermitidaNoSite($dia)) {
            return [];
        }

        $profissionais = $this->profissionaisAptas($servico, $profissionalId);
        if ($profissionais->isEmpty()) {
            return [];
        }

        $ids = $profissionais->pluck('id')->all();
        $ocupados = $this->ocupadosNoDia($ids, $dia, $ignorarAgendamentoId);
        $bloqueios = $this->bloqueiosNoDia($ids, $dia);
        $passo = max(5, Setting::int('intervalo_slots_minutos'));
        $minimo = $this->inicioMinimo($modo);

        $resultado = [];
        foreach ($profissionais as $prof) {
            $duracao = $this->infoServico($servico, $prof)['duracao'];
            $duracaoBloqueio = $duracao + $servico->intervalo_apos_minutos;

            foreach ($prof->horarios->where('dia_semana', $dia->dayOfWeek) as $h) {
                $abre = $dia->copy()->setTimeFromTimeString($h->hora_inicio);
                $fecha = $dia->copy()->setTimeFromTimeString($h->hora_fim);

                for ($t = $abre->copy(); $t->copy()->addMinutes($duracao)->lte($fecha); $t->addMinutes($passo)) {
                    $fim = $t->copy()->addMinutes($duracao);
                    $fimBloqueio = $t->copy()->addMinutes($duracaoBloqueio);

                    if ($t->lt($minimo)
                        || $this->cruzaIntervalo($h, $dia, $t, $fim)
                        || $this->colideBloqueio($bloqueios, $prof->id, $t, $fim)
                        || $this->colideAgendamento($ocupados->get($prof->id, collect()), $t, $fimBloqueio)) {
                        continue;
                    }

                    $resultado[$t->format('H:i')][] = $prof->id;
                }
            }
        }

        ksort($resultado);

        return collect($resultado)->map(fn ($profs, $hora) => ['hora' => $hora, 'profissionais' => array_values(array_unique($profs))])->values()->all();
    }

    /**
     * Valida um horário específico. Retorna null se disponível ou a mensagem do motivo.
     * $encaixe = true (só admin): permite fora do expediente, mas respeita bloqueios e conflitos.
     */
    public function verificarHorario(Servico $servico, Profissional $prof, Carbon $inicio, string $modo = self::MODO_SITE, ?int $ignorarAgendamentoId = null, bool $encaixe = false): ?string
    {
        $prof->loadMissing('horarios');
        $duracao = $this->infoServico($servico, $prof)['duracao'];
        $fim = $inicio->copy()->addMinutes($duracao);
        $fimBloqueio = $fim->copy()->addMinutes($servico->intervalo_apos_minutos);
        $dia = $inicio->copy()->startOfDay();

        if (! $prof->ativo) {
            return 'Esta profissional não está disponível.';
        }
        if (! $this->profissionalRealizaServico($prof, $servico)) {
            return 'Esta profissional não realiza este serviço.';
        }
        if ($modo === self::MODO_SITE && ! $this->dataPermitidaNoSite($dia)) {
            return 'Data fora do período permitido para agendamento.';
        }
        if (! $encaixe && $inicio->lt($this->inicioMinimo($modo))) {
            return $modo === self::MODO_SITE
                ? 'Agendamentos precisam de ' . Setting::int('antecedencia_minima_horas') . 'h de antecedência.'
                : 'Esse horário já passou.';
        }
        if (! $encaixe && ! $this->dentroDoExpediente($prof, $inicio, $fim)) {
            return 'Horário fora do expediente da profissional.';
        }
        if ($this->colideBloqueio($this->bloqueiosNoDia([$prof->id], $dia), $prof->id, $inicio, $fim)) {
            return 'A agenda está bloqueada nesse horário.';
        }
        $ocupados = $this->ocupadosNoDia([$prof->id], $dia, $ignorarAgendamentoId)->get($prof->id, collect());
        if ($this->colideAgendamento($ocupados, $inicio, $fimBloqueio)) {
            return 'Esse horário já está ocupado.';
        }

        return null;
    }

    /** "Sem preferência": profissional disponível no horário com menos agendamentos no dia. */
    public function escolherProfissional(Servico $servico, Carbon $inicio, string $modo = self::MODO_SITE): ?Profissional
    {
        $candidatas = $this->profissionaisAptas($servico)
            ->filter(fn (Profissional $p) => $this->verificarHorario($servico, $p, $inicio, $modo) === null);

        if ($candidatas->isEmpty()) {
            return null;
        }

        $dia = $inicio->copy()->startOfDay();
        $contagem = Agendamento::ativos()
            ->whereIn('profissional_id', $candidatas->pluck('id'))
            ->where('inicio', '>=', $dia)->where('inicio', '<', $dia->copy()->addDay())
            ->selectRaw('profissional_id, count(*) as total')->groupBy('profissional_id')
            ->pluck('total', 'profissional_id');

        return $candidatas->sortBy(fn ($p) => [(int) ($contagem[$p->id] ?? 0), $p->ordem])->first();
    }

    public function dataPermitidaNoSite(Carbon $dia): bool
    {
        return $dia->gte(today()) && $dia->lte(today()->addDays(Setting::int('dias_maximos_agendamento')));
    }

    public function dentroDoExpediente(Profissional $prof, Carbon $inicio, Carbon $fim): bool
    {
        if (! $inicio->isSameDay($fim->copy()->subSecond())) {
            return false;
        }
        $dia = $inicio->copy()->startOfDay();

        foreach ($prof->horarios->where('dia_semana', $inicio->dayOfWeek) as $h) {
            $abre = $dia->copy()->setTimeFromTimeString($h->hora_inicio);
            $fecha = $dia->copy()->setTimeFromTimeString($h->hora_fim);
            if ($inicio->gte($abre) && $fim->lte($fecha) && ! $this->cruzaIntervalo($h, $dia, $inicio, $fim)) {
                return true;
            }
        }

        return false;
    }

    private function inicioMinimo(string $modo): Carbon
    {
        return $modo === self::MODO_SITE ? now()->addHours(Setting::int('antecedencia_minima_horas')) : now();
    }

    private function cruzaIntervalo($horario, Carbon $dia, Carbon $inicio, Carbon $fim): bool
    {
        if (! $horario->intervalo_inicio || ! $horario->intervalo_fim) {
            return false;
        }
        $ii = $dia->copy()->setTimeFromTimeString($horario->intervalo_inicio);
        $if = $dia->copy()->setTimeFromTimeString($horario->intervalo_fim);

        return $inicio->lt($if) && $fim->gt($ii);
    }

    private function ocupadosNoDia(array $ids, Carbon $dia, ?int $ignorarId): Collection
    {
        return Agendamento::ativos()
            ->whereIn('profissional_id', $ids)
            ->where('inicio', '<', $dia->copy()->addDay())
            ->where('fim_bloqueio', '>', $dia)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->get(['id', 'profissional_id', 'inicio', 'fim_bloqueio'])
            ->groupBy('profissional_id');
    }

    private function bloqueiosNoDia(array $ids, Carbon $dia): Collection
    {
        return BloqueioAgenda::query()
            ->where(fn ($q) => $q->whereNull('profissional_id')->orWhereIn('profissional_id', $ids))
            ->where('inicio', '<', $dia->copy()->addDay())
            ->where('fim', '>', $dia)
            ->get(['id', 'profissional_id', 'inicio', 'fim']);
    }

    private function colideBloqueio(Collection $bloqueios, int $profId, Carbon $inicio, Carbon $fim): bool
    {
        return $bloqueios->contains(fn ($b) => ($b->profissional_id === null || $b->profissional_id === $profId)
            && $inicio->lt($b->fim) && $fim->gt($b->inicio));
    }

    private function colideAgendamento(Collection $ocupados, Carbon $inicio, Carbon $fimBloqueio): bool
    {
        return $ocupados->contains(fn ($a) => $inicio->lt($a->fim_bloqueio) && $fimBloqueio->gt($a->inicio));
    }
}
