@extends('painel.layout')
@section('titulo', 'Lista de espera')

@section('conteudo')
@include('painel.admin.relacionamento._abas')
<div class="mb-4 flex flex-wrap gap-2 text-sm">
    @foreach (['aguardando' => 'Aguardando', 'contatada' => 'Contatadas', 'convertida' => 'Convertidas', 'expirada' => 'Expiradas', 'todas' => 'Todas'] as $s => $r)
        <a href="{{ route('admin.relacionamento.lista-espera', ['status' => $s]) }}" class="pilula {{ $status === $s ? 'pilula-ativa' : 'bg-white' }}">{{ $r }}</a>
    @endforeach
</div>

<div class="space-y-3">
    @forelse ($entradas as $e)
        <div class="card flex flex-wrap items-center gap-3 p-4 {{ $e->vaga_liberada_em && $e->status === 'aguardando' ? 'ring-2 ring-emerald-300' : '' }}">
            <div class="min-w-[14rem] flex-1">
                <a href="{{ route('admin.clientes.show', $e->cliente) }}" class="link font-medium">{{ $e->cliente->nome }}</a>
                <p class="text-sm text-gray-700">{{ $e->servico->nome }}{{ $e->profissional ? ' com ' . $e->profissional->nome_exibicao : '' }} · {{ $e->data_desejada->format('d/m/Y') }} · {{ \App\Models\ListaEspera::PERIODOS[$e->periodo_preferido] }}</p>
                @if ($e->vaga_liberada_em && $e->status === 'aguardando')<p class="text-xs font-medium text-emerald-800">Vaga liberada em {{ $e->vaga_liberada_em->format('d/m H:i') }}</p>@endif
                @if ($e->agendamento)<p class="text-xs"><a class="link" href="{{ route('admin.agendamentos.show', $e->agendamento) }}">Convertida: {{ $e->agendamento->codigo }}</a></p>@endif
            </div>
            <span class="rounded-full bg-luna-cream px-3 py-1 text-xs">{{ \App\Models\ListaEspera::STATUS[$e->status] }}</span>
            @if (in_array($e->status, ['aguardando', 'contatada']))
                <a href="{{ \App\Support\WhatsAppLink::listaEspera($e) }}" target="_blank" rel="noopener" class="btn-secundario btn-sm"><x-icone nome="whatsapp" class="w-4 h-4 text-green-700" /> Chamar</a>
                @if ($e->status === 'aguardando')
                    <form method="POST" action="{{ route('admin.relacionamento.lista-espera.status', $e) }}">@csrf<button name="status" value="contatada" class="btn-secundario btn-sm">Marcar contatada</button></form>
                @endif
                <a href="{{ route('admin.agendamentos.create', ['lista_espera_id' => $e->id]) }}" class="btn-primario btn-sm">Converter em agendamento</a>
                <form method="POST" action="{{ route('admin.relacionamento.lista-espera.status', $e) }}">@csrf<button name="status" value="expirada" class="btn-secundario btn-sm">Encerrar</button></form>
            @endif
        </div>
    @empty
        <x-card><p class="text-sm text-gray-600">Nenhuma entrada.</p></x-card>
    @endforelse
</div>
<div class="mt-4">{{ $entradas->links() }}</div>
@endsection
