<?php

namespace App\Http\Controllers\Site;

use App\Exceptions\AgendamentoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\AgendarRequest;
use App\Http\Requests\Site\ListaEsperaRequest;
use App\Models\Agendamento;
use App\Services\AgendamentoService;
use App\Services\ListaEsperaService;
use App\Support\CalendarioLinks;
use App\Support\WhatsAppLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AgendamentoController extends Controller
{
    public function store(AgendarRequest $request, AgendamentoService $service): RedirectResponse
    {
        try {
            $r = $service->criarPeloSite($request->validated());
        } catch (AgendamentoException $e) {
            return redirect()->to(url('/') . '#agendar')
                ->withInput($request->except('website'))
                ->withErrors(['agendamento' => $e->getMessage()])
                ->with('sugerir_whatsapp', $e->sugerirWhatsapp);
        }

        return redirect()
            ->route('agendamento.confirmado', $r['agendamento']->token_publico)
            ->with('abrir_whatsapp', true)
            ->with('cliente_recorrente', $r['cliente_recorrente']);
    }

    public function confirmado(string $token): View
    {
        $agendamento = $this->porToken($token);

        return view('site.confirmado', [
            'ag' => $agendamento,
            'linkWhatsapp' => WhatsAppLink::paraSalao($agendamento),
            'linkGoogle' => CalendarioLinks::google($agendamento),
            'abrirWhatsapp' => (bool) session('abrir_whatsapp'),
            'clienteRecorrente' => (bool) session('cliente_recorrente'),
        ]);
    }

    /** Chamado por navigator.sendBeacon ao clicar em "Enviar no WhatsApp". */
    public function whatsappEnviado(string $token, AgendamentoService $service): Response
    {
        $service->registrarWhatsappEnviado($this->porToken($token));

        return response()->noContent();
    }

    public function ics(string $token): Response
    {
        $ag = $this->porToken($token);

        return response(CalendarioLinks::ics($ag), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="agendamento-' . $ag->codigo . '.ics"',
        ]);
    }

    public function listaEspera(ListaEsperaRequest $request, ListaEsperaService $service): RedirectResponse
    {
        $service->adicionarPeloSite($request->validated());

        return redirect()->to(url('/') . '#agendar')
            ->with('lista_espera_ok', 'Pronto! Você entrou na lista de espera. Se abrir um horário, o salão chama você no WhatsApp. 💛');
    }

    private function porToken(string $token): Agendamento
    {
        abort_unless(strlen($token) === 40, 404);

        return Agendamento::with(['cliente', 'servico', 'profissional'])->where('token_publico', $token)->firstOrFail();
    }
}
