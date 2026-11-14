@extends('painel.layout')
@section('titulo', 'Site e configurações')

@section('conteudo')
@include('painel.admin.site._abas')
<form method="POST" action="{{ route('admin.site.configuracoes.update') }}" class="grid max-w-5xl gap-6 lg:grid-cols-2">
    @csrf @method('PUT')
    <x-card titulo="Dados do salão">
        <div class="space-y-4">
            <x-input nome="nome_salao" rotulo="Nome" :valor="$config['nome_salao']" required />
            <x-input nome="slogan" rotulo="Slogan" :valor="$config['slogan']" required />
            <x-input nome="sobre" rotulo="Texto “Sobre”" tipo="textarea" rows="4" :valor="$config['sobre']" />
            <x-input nome="whatsapp_salao" rotulo="WhatsApp do salão (recebe os agendamentos)" :valor="\App\Support\Telefone::formatar($config['whatsapp_salao'])" required ajuda="Os pedidos do site são enviados para este número via wa.me." />
            <x-input nome="instagram" rotulo="Instagram" :valor="'@' . ltrim($config['instagram'], '@')" />
            <x-input nome="endereco" rotulo="Endereço" :valor="$config['endereco']" />
            <x-input nome="link_maps" rotulo="Link do Google Maps" tipo="url" :valor="$config['link_maps']" />
            <x-input nome="horario_funcionamento" rotulo="Horário de funcionamento (texto do site)" :valor="$config['horario_funcionamento']" />
        </div>
    </x-card>

    <x-card titulo="Regras de agendamento">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input nome="antecedencia_minima_horas" rotulo="Antecedência mínima (h)" tipo="number" min="0" :valor="$config['antecedencia_minima_horas']" />
            <x-input nome="dias_maximos_agendamento" rotulo="Agendar até (dias à frente)" tipo="number" min="1" :valor="$config['dias_maximos_agendamento']" />
            <x-select nome="intervalo_slots_minutos" rotulo="Intervalo entre horários" :valor="$config['intervalo_slots_minutos']" :opcoes="[10 => '10 min', 15 => '15 min', 20 => '20 min', 30 => '30 min', 45 => '45 min', 60 => '60 min']" />
            <x-input nome="limite_agendamentos_pendentes_por_cliente" rotulo="Máx. pendentes por cliente" tipo="number" min="1" :valor="$config['limite_agendamentos_pendentes_por_cliente']" />
            <x-input nome="expirar_pendente_horas" rotulo="Cancelar pendentes após (h)" tipo="number" min="0" :valor="$config['expirar_pendente_horas']" ajuda="0 = nunca" />
            <x-input nome="faltas_para_bloqueio" rotulo="Faltas para bloquear online" tipo="number" min="0" :valor="$config['faltas_para_bloqueio']" ajuda="0 = não bloquear" />
            <x-input nome="antecedencia_cancelamento_horas" rotulo="Prazo p/ profissional reagendar (h)" tipo="number" min="0" :valor="$config['antecedencia_cancelamento_horas']" />
            <x-input nome="limite_reagendamentos_por_agendamento" rotulo="Máx. reagendamentos (profissional)" tipo="number" min="0" :valor="$config['limite_reagendamentos_por_agendamento']" />
            <x-input nome="dias_cliente_inativa" rotulo="Cliente inativa após (dias)" tipo="number" min="15" :valor="$config['dias_cliente_inativa']" />
        </div>
        <div class="mt-5 space-y-2 text-sm">
            <p class="font-medium">Permissões das profissionais</p>
            @foreach (['profissional_pode_reagendar' => 'Podem reagendar os próprios atendimentos', 'profissional_pode_cancelar' => 'Podem cancelar os próprios atendimentos', 'profissional_gerencia_bloqueios' => 'Podem gerenciar os próprios bloqueios de agenda'] as $chave => $rotulo)
                <label class="flex items-center gap-2"><input type="checkbox" name="{{ $chave }}" value="1" @checked(filter_var($config[$chave], FILTER_VALIDATE_BOOLEAN)) class="rounded border-gray-300"> {{ $rotulo }}</label>
            @endforeach
        </div>
    </x-card>

    <div class="lg:col-span-2"><x-button>Salvar configurações</x-button></div>
</form>
@endsection
