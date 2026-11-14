<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Enums\PapelUsuario;
use App\Http\Controllers\Controller;
use App\Models\BloqueioAgenda;
use App\Models\CategoriaServico;
use App\Models\Profissional;
use App\Models\User;
use App\Support\Dinheiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfissionalController extends Controller
{
    public function index(): View
    {
        return view('painel.admin.profissionais.index', [
            'profissionais' => Profissional::with('user')->withCount('servicos')->orderBy('ordem')->orderBy('nome')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Profissional(['ativo' => true, 'cor_agenda' => '#B08D57', 'percentual_comissao' => 40]));
    }

    public function store(Request $request): RedirectResponse
    {
        $prof = Profissional::create($this->dados($request));

        // Expediente padrão seg–sáb 09–18 (editável na aba Horários)
        foreach (range(1, 6) as $dia) {
            $prof->horarios()->create(['dia_semana' => $dia, 'hora_inicio' => '09:00', 'hora_fim' => '18:00', 'intervalo_inicio' => '12:00', 'intervalo_fim' => '13:00']);
        }

        return redirect()->route('admin.profissionais.edit', [$prof, 'aba' => 'acesso'])->with('sucesso', 'Profissional criada. Agora crie o acesso e vincule os serviços.');
    }

    public function edit(Profissional $profissional): View
    {
        return $this->form($profissional);
    }

    public function update(Request $request, Profissional $profissional): RedirectResponse
    {
        $profissional->update($this->dados($request, $profissional));
        if ($profissional->user && ! $profissional->ativo) {
            $profissional->user->update(['ativo' => false]);
        }

        return redirect()->route('admin.profissionais.edit', [$profissional, 'aba' => 'dados'])->with('sucesso', 'Dados atualizados.');
    }

    public function destroy(Profissional $profissional): RedirectResponse
    {
        if ($profissional->agendamentos()->exists()) {
            $profissional->update(['ativo' => false]);
            $profissional->user?->update(['ativo' => false]);

            return redirect()->route('admin.profissionais.index')->with('sucesso', 'A profissional tem histórico e foi desativada (acesso bloqueado).');
        }

        DB::transaction(function () use ($profissional) {
            $user = $profissional->user;
            $profissional->delete();
            $user?->delete();
        });

        return redirect()->route('admin.profissionais.index')->with('sucesso', 'Profissional excluída.');
    }

    /** Cria/atualiza o usuário de acesso ao painel. */
    public function acesso(Request $request, Profissional $profissional): RedirectResponse
    {
        $user = $profissional->user;
        $dados = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'ativo' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($profissional, $user, $dados, $request) {
            $attrs = ['nome' => $profissional->nome, 'email' => $dados['email'], 'papel' => PapelUsuario::Profissional, 'ativo' => $request->boolean('ativo')];
            if (! empty($dados['password'])) {
                $attrs['password'] = $dados['password'];
            }
            if ($user) {
                $user->update($attrs);
            } else {
                $user = User::create($attrs);
                $profissional->update(['user_id' => $user->id]);
            }
        });

        return redirect()->route('admin.profissionais.edit', [$profissional, 'aba' => 'acesso'])->with('sucesso', 'Acesso salvo.');
    }

    /** Serviços que realiza + sobrescritas de duração/preço. */
    public function servicos(Request $request, Profissional $profissional): RedirectResponse
    {
        $dados = $request->validate([
            'servicos' => ['nullable', 'array'],
            'servicos.*.ativo' => ['nullable', 'boolean'],
            'servicos.*.duracao_minutos' => ['nullable', 'integer', 'min:5', 'max:720'],
            'servicos.*.preco' => ['nullable', 'regex:/^\d{1,7}([.,]\d{1,2})?$/'],
        ]);

        $sync = [];
        foreach ($dados['servicos'] ?? [] as $servicoId => $s) {
            if (! empty($s['ativo'])) {
                $sync[$servicoId] = [
                    'duracao_minutos' => $s['duracao_minutos'] ?: null,
                    'preco' => isset($s['preco']) && $s['preco'] !== '' ? Dinheiro::paraDecimal($s['preco']) : null,
                ];
            }
        }
        $profissional->servicos()->sync($sync);

        return redirect()->route('admin.profissionais.edit', [$profissional, 'aba' => 'servicos'])->with('sucesso', 'Serviços atualizados.');
    }

    public function horarios(Request $request, Profissional $profissional): RedirectResponse
    {
        $dados = $request->validate([
            'dias' => ['nullable', 'array'],
            'dias.*.trabalha' => ['nullable', 'boolean'],
            'dias.*.hora_inicio' => ['nullable', 'required_if:dias.*.trabalha,1', 'date_format:H:i'],
            'dias.*.hora_fim' => ['nullable', 'required_if:dias.*.trabalha,1', 'date_format:H:i'],
            'dias.*.intervalo_inicio' => ['nullable', 'date_format:H:i'],
            'dias.*.intervalo_fim' => ['nullable', 'date_format:H:i', 'required_with:dias.*.intervalo_inicio'],
        ]);

        foreach ($dados['dias'] ?? [] as $dia => $d) {
            if (! empty($d['trabalha']) && $d['hora_fim'] <= $d['hora_inicio']) {
                return back()->withInput()->with('erro', 'O fim do expediente deve ser depois do início.');
            }
        }

        DB::transaction(function () use ($profissional, $dados) {
            $profissional->horarios()->delete();
            foreach ($dados['dias'] ?? [] as $dia => $d) {
                if (empty($d['trabalha'])) {
                    continue;
                }
                $profissional->horarios()->create([
                    'dia_semana' => (int) $dia,
                    'hora_inicio' => $d['hora_inicio'],
                    'hora_fim' => $d['hora_fim'],
                    'intervalo_inicio' => $d['intervalo_inicio'] ?: null,
                    'intervalo_fim' => $d['intervalo_inicio'] ? $d['intervalo_fim'] : null,
                ]);
            }
        });

        return redirect()->route('admin.profissionais.edit', [$profissional, 'aba' => 'horarios'])->with('sucesso', 'Horários de trabalho atualizados.');
    }

    public function bloquear(Request $request, Profissional $profissional): RedirectResponse
    {
        BloqueioAgenda::create(['profissional_id' => $profissional->id] + self::validarBloqueio($request));

        return redirect()->route('admin.profissionais.edit', [$profissional, 'aba' => 'bloqueios'])->with('sucesso', 'Bloqueio adicionado.');
    }

    public function desbloquear(BloqueioAgenda $bloqueio): RedirectResponse
    {
        $bloqueio->delete();

        return back()->with('sucesso', 'Bloqueio removido.');
    }

    public static function validarBloqueio(Request $request): array
    {
        $d = $request->validate([
            'data_inicio' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'data_fim' => ['required', 'date_format:Y-m-d', 'after_or_equal:data_inicio'],
            'hora_fim' => ['nullable', 'date_format:H:i'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $inicio = Carbon::parse($d['data_inicio'] . ' ' . ($d['hora_inicio'] ?? '00:00'));
        $fim = $d['hora_fim'] ?? null
            ? Carbon::parse($d['data_fim'] . ' ' . $d['hora_fim'])
            : Carbon::parse($d['data_fim'])->addDay()->startOfDay();

        if ($fim->lte($inicio)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['hora_fim' => 'O fim do bloqueio deve ser depois do início.']);
        }

        return ['inicio' => $inicio, 'fim' => $fim, 'motivo' => $d['motivo'] ?? null];
    }

    private function form(Profissional $profissional): View
    {
        $profissional->loadMissing(['user', 'servicos', 'horarios']);

        return view('painel.admin.profissionais.form', [
            'prof' => $profissional,
            'aba' => request('aba', 'dados'),
            'categorias' => CategoriaServico::with('servicos')->orderBy('ordem')->get(),
            'bloqueios' => $profissional->exists
                ? $profissional->bloqueios()->where('fim', '>=', now()->subDays(1))->orderBy('inicio')->get()
                : collect(),
        ]);
    }

    private function dados(Request $request, ?Profissional $prof = null): array
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'apelido' => ['nullable', 'string', 'max:40'],
            'telefone' => ['nullable', 'string', 'max:25'],
            'bio' => ['nullable', 'string', 'max:500'],
            'cor_agenda' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'percentual_comissao' => ['required', 'numeric', 'min:0', 'max:100'],
            'ordem' => ['nullable', 'integer', 'min:0'],
            'foto' => ['nullable', 'image', 'max:4096'],
        ]);
        $dados['ativo'] = $request->boolean('ativo');
        $dados['ordem'] = (int) ($dados['ordem'] ?? 0);

        if ($request->hasFile('foto')) {
            if ($prof?->foto) {
                Storage::disk('public')->delete($prof->foto);
            }
            $dados['foto'] = $request->file('foto')->store('profissionais', 'public');
        } else {
            unset($dados['foto']);
        }

        return $dados;
    }
}
