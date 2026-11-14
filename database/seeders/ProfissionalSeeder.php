<?php

namespace Database\Seeders;

use App\Enums\PapelUsuario;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProfissionalSeeder extends Seeder
{
    public function run(): void
    {
        $senha = env('ADMIN_PASSWORD');

        $this->cleide();

        $exemplos = [
            [
                'email' => 'ana@cleideluna.com.br', 'nome' => 'Ana Paula Lima', 'apelido' => 'Ana', 'cor' => '#B08D57', 'comissao' => 40,
                'bio' => 'Colorista especialista em loiros e descoloração.',
                'servicos' => ['terapia-dos-fios', 'corte-e-escova', 'mechas-personalizadas', 'liso-premium'],
            ],
            [
                'email' => 'bia@cleideluna.com.br', 'nome' => 'Beatriz Souza', 'apelido' => 'Bia', 'cor' => '#8A6A4F', 'comissao' => 35,
                'bio' => 'Designer de sobrancelhas, nail designer e maquiadora.',
                'servicos' => ['nails-spa', 'design-de-sobrancelhas-e-epilacao', 'maquiagem-e-penteado', 'corte-e-escova'],
            ],
        ];

        foreach ($exemplos as $ordem => $e) {
            $user = User::updateOrCreate(['email' => $e['email']], [
                'nome' => $e['nome'], 'password' => $senha, 'papel' => PapelUsuario::Profissional, 'ativo' => true,
            ]);

            $prof = Profissional::updateOrCreate(['user_id' => $user->id], [
                'nome' => $e['nome'], 'apelido' => $e['apelido'], 'bio' => $e['bio'], 'cor_agenda' => $e['cor'],
                'percentual_comissao' => $e['comissao'], 'ativo' => true, 'ordem' => $ordem + 1,
            ]);

            $prof->servicos()->sync(Servico::whereIn('slug', $e['servicos'])->pluck('id'));

            // Segunda a sábado, 09:00–18:00 com intervalo 12:00–13:00
            $prof->horarios()->delete();
            foreach (range(1, 6) as $dia) {
                $prof->horarios()->create([
                    'dia_semana' => $dia, 'hora_inicio' => '09:00', 'hora_fim' => '18:00',
                    'intervalo_inicio' => '12:00', 'intervalo_fim' => '13:00',
                ]);
            }
        }
    }

    /**
     * Cleide, dona do salão, na equipe. Sem usuário próprio: ela já acessa o painel
     * pela conta admin (vincular aqui desativaria o admin se a profissional fosse desativada).
     * Só cria se ainda não existir, para não sobrescrever ajustes feitos pelo painel.
     */
    private function cleide(): void
    {
        if (Profissional::where('nome', 'Cleide Luna')->exists()) {
            return;
        }

        $prof = Profissional::create([
            'nome' => 'Cleide Luna', 'apelido' => 'Cleide', 'cor_agenda' => '#C9A27E',
            'bio' => 'Fundadora do salão, cabeleireira e especialista em beleza.',
            'percentual_comissao' => 0, 'ativo' => true, 'ordem' => 0,
        ]);

        $prof->servicos()->sync(Servico::pluck('id'));

        // Segunda a sábado, 09:00–18:00 com intervalo 12:00–13:00
        foreach (range(1, 6) as $dia) {
            $prof->horarios()->create([
                'dia_semana' => $dia, 'hora_inicio' => '09:00', 'hora_fim' => '18:00',
                'intervalo_inicio' => '12:00', 'intervalo_fim' => '13:00',
            ]);
        }
    }
}
