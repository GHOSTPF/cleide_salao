<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Http\Controllers\Controller;
use App\Rules\TelefoneValido;
use App\Support\Atividade;
use App\Support\Setting;
use App\Support\Telefone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfiguracaoController extends Controller
{
    public function edit(): View
    {
        return view('painel.admin.site.configuracoes', ['config' => Setting::todas()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'nome_salao' => ['required', 'string', 'max:80'],
            'slogan' => ['required', 'string', 'max:120'],
            'sobre' => ['nullable', 'string', 'max:1500'],
            'whatsapp_salao' => ['required', new TelefoneValido],
            'instagram' => ['nullable', 'string', 'max:60'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'link_maps' => ['nullable', 'url', 'max:500'],
            'horario_funcionamento' => ['nullable', 'string', 'max:255'],
            'antecedencia_minima_horas' => ['required', 'integer', 'min:0', 'max:168'],
            'dias_maximos_agendamento' => ['required', 'integer', 'min:1', 'max:365'],
            'intervalo_slots_minutos' => ['required', 'integer', 'in:10,15,20,30,45,60'],
            'antecedencia_cancelamento_horas' => ['required', 'integer', 'min:0', 'max:168'],
            'limite_reagendamentos_por_agendamento' => ['required', 'integer', 'min:0', 'max:10'],
            'limite_agendamentos_pendentes_por_cliente' => ['required', 'integer', 'min:1', 'max:10'],
            'faltas_para_bloqueio' => ['required', 'integer', 'min:0', 'max:20'],
            'expirar_pendente_horas' => ['required', 'integer', 'min:0', 'max:720'],
            'dias_cliente_inativa' => ['required', 'integer', 'min:15', 'max:730'],
        ]);

        $dados['whatsapp_salao'] = Telefone::normalizar($dados['whatsapp_salao']);
        $dados['instagram'] = ltrim((string) ($dados['instagram'] ?? ''), '@');
        foreach (Setting::BOOLEANAS as $flag) {
            $dados[$flag] = $request->boolean($flag);
        }

        $antes = array_intersect_key(Setting::todas(), $dados);
        Setting::setMuitas($dados);
        Atividade::registrar('alterou_configuracoes', null, $antes, $dados, 'Configuracao');

        return back()->with('sucesso', 'Configurações salvas.');
    }
}
