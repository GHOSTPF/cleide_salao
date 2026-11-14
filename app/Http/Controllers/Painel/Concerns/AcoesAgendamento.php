<?php

namespace App\Http\Controllers\Painel\Concerns;

use App\Enums\CanceladoPor;
use App\Exceptions\AgendamentoException;
use App\Http\Requests\Painel\ConcluirAtendimentoRequest;
use App\Http\Requests\Painel\ReagendarRequest;
use App\Models\Agendamento;
use App\Models\AtendimentoAnotacao;
use App\Models\Profissional;
use App\Services\AgendamentoService;
use App\Services\DisponibilidadeService;
use App\Services\ReagendamentoService;
use App\Support\WhatsAppLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Ações sobre um agendamento compartilhadas entre o painel da admin e o da profissional.
 * A autorização é sempre feita pela AgendamentoPolicy.
 */
trait AcoesAgendamento
{
    /** Prefixo das rotas: 'admin.' ou 'profissional.' */
    abstract protected function prefixo(): string;

    protected function voltarParaDetalhe(Agendamento $ag): RedirectResponse
    {
        return redirect()->route($this->prefixo() . 'agendamentos.show', $ag);
    }

    protected function executar(callable $acao, string $mensagem, Agendamento $ag, ?callable $extras = null): RedirectResponse
    {
        try {
            $resultado = $acao();
        } catch (AgendamentoException $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }

        $destino = $resultado instanceof Agendamento ? $resultado : $ag;
        $resposta = $this->voltarParaDetalhe($destino)->with('sucesso', $mensagem);

        return $extras ? $extras($resposta, $destino) : $resposta;
    }

    public function confirmar(Agendamento $agendamento, AgendamentoService $svc): RedirectResponse
    {
        $this->authorize('confirmar', $agendamento);

        return $this->executar(fn () => $svc->confirmar($agendamento, auth()->user()), 'Agendamento confirmado.', $agendamento);
    }

    public function cancelar(Request $request, Agendamento $agendamento, AgendamentoService $svc): RedirectResponse
    {
        $this->authorize('cancelar', $agendamento);
        $dados = $request->validate([
            'cancelado_por' => ['required', Rule::in([CanceladoPor::Cliente->value, CanceladoPor::Salao->value])],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->executar(
            fn () => $svc->cancelar($agendamento, auth()->user(), CanceladoPor::from($dados['cancelado_por']), $dados['motivo'] ?? null),
            'Agendamento cancelado. O horário foi liberado.',
            $agendamento,
        );
    }

    public function falta(Agendamento $agendamento, AgendamentoService $svc): RedirectResponse
    {
        $this->authorize('marcarFalta', $agendamento);

        return $this->executar(fn () => $svc->marcarFalta($agendamento, auth()->user()), 'Falta registrada.', $agendamento);
    }

    public function concluir(ConcluirAtendimentoRequest $request, Agendamento $agendamento, AgendamentoService $svc): RedirectResponse
    {
        return $this->executar(
            fn () => $svc->concluir($agendamento, $request->user(), $request->input('valor_cobrado'), $request->input('pagamentos', []), $request->input('anotacao')),
            'Atendimento concluído e pagamento registrado.',
            $agendamento,
        );
    }

    public function reagendarForm(Agendamento $agendamento, ReagendamentoService $reag, DisponibilidadeService $disp): View|RedirectResponse
    {
        $this->authorize('reagendar', $agendamento);
        $agendamento->load(['cliente', 'servico', 'profissional']);

        if ($erro = $reag->impedimento($agendamento, auth()->user())) {
            return $this->voltarParaDetalhe($agendamento)->with('erro', $erro);
        }

        return view('painel.agendamentos.reagendar', [
            'ag' => $agendamento,
            'prefixo' => $this->prefixo(),
            'profissionais' => auth()->user()->isAdmin()
                ? $disp->profissionaisAptas($agendamento->servico)
                : collect([$agendamento->profissional]),
        ]);
    }

    public function reagendar(ReagendarRequest $request, Agendamento $agendamento, ReagendamentoService $reag, AgendamentoService $svc): RedirectResponse
    {
        $dados = $request->validated();
        $prof = ! empty($dados['profissional_id']) ? Profissional::find($dados['profissional_id']) : null;
        $pedidoPor = $request->user()->isProfissional() && $dados['pedido_por'] === 'salao' ? 'profissional' : $dados['pedido_por'];

        try {
            $novo = $reag->reagendar(
                $agendamento,
                $svc->montarInicio($dados['data'], $dados['hora']),
                $prof,
                $request->user(),
                $pedidoPor,
                $dados['motivo'] ?? null,
                (bool) ($dados['encaixe'] ?? false),
            );
        } catch (AgendamentoException $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }

        return $this->voltarParaDetalhe($novo)
            ->with('sucesso', 'Reagendado para ' . $novo->inicio->format('d/m/Y \à\s H:i') . ' (' . $novo->codigo . ').')
            ->with('link_whatsapp', WhatsAppLink::avisoReagendamento($novo))
            ->with('link_whatsapp_rotulo', 'Avisar cliente no WhatsApp');
    }

    public function anotacao(Request $request, Agendamento $agendamento): RedirectResponse
    {
        $this->authorize('update', $agendamento);
        $dados = $request->validate(['anotacao' => ['required', 'string', 'max:2000']]);

        AtendimentoAnotacao::create([
            'cliente_id' => $agendamento->cliente_id,
            'agendamento_id' => $agendamento->id,
            'profissional_id' => $agendamento->profissional_id,
            'anotacao' => $dados['anotacao'],
        ]);

        return $this->voltarParaDetalhe($agendamento)->with('sucesso', 'Anotação técnica salva.');
    }

    /** Slots do painel (sem antecedência mínima). */
    public function horarios(Request $request, DisponibilidadeService $disp): JsonResponse
    {
        $dados = $request->validate([
            'servico_id' => ['required', 'integer', 'exists:servicos,id'],
            'profissional_id' => ['required', 'integer'],
            'data' => ['required', 'date_format:Y-m-d'],
            'ignorar' => ['nullable', 'integer'],
        ]);

        $profId = (int) $dados['profissional_id'];
        if (auth()->user()->isProfissional()) {
            $profId = auth()->user()->profissional->id; // profissional só consulta a própria agenda
        }

        $servico = \App\Models\Servico::findOrFail($dados['servico_id']);

        return response()->json($disp->slots($servico, $profId, $dados['data'], DisponibilidadeService::MODO_PAINEL, $dados['ignorar'] ?? null));
    }
}
