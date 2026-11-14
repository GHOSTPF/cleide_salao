@extends('painel.layout')
@section('titulo', 'Minhas clientes')

@section('conteudo')
<form method="GET" class="mb-5 flex max-w-md gap-2">
    <div class="relative flex-1">
        <x-icone nome="busca" class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-gray-400" />
        <input name="q" value="{{ $q }}" class="campo pl-9" placeholder="Buscar por nome" aria-label="Buscar por nome">
    </div>
    <x-button variante="secundario">Buscar</x-button>
</form>

@if ($clientes->isEmpty())
    <div class="rounded-lg border border-dashed border-luna-nude px-6 py-10 text-center text-sm text-gray-600">
        {{ $q ? 'Nenhuma cliente encontrada com “' . $q . '”.' : 'Você ainda não atendeu nenhuma cliente.' }}
    </div>
@else
    <ul class="overflow-hidden rounded-lg border border-luna-sand bg-white">
        @foreach ($clientes as $c)
            <li class="border-b border-luna-sand/70 last:border-0">
                <a href="{{ route('profissional.clientes.show', $c) }}" class="group flex items-center gap-4 px-4 py-3 transition-colors hover:bg-luna-cream/50">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium group-hover:underline group-hover:decoration-luna-nude group-hover:underline-offset-2">{{ $c->nome }}</p>
                        <p class="text-sm tabular-nums text-gray-500">{{ $c->telefone_formatado }}</p>
                    </div>
                    <div class="hidden text-right text-sm sm:block">
                        <p class="legenda">Última vez</p>
                        <p class="tabular-nums">{{ $c->ultimo_atendimento ? \Illuminate\Support\Carbon::parse($c->ultimo_atendimento)->format('d/m/Y') : '—' }}</p>
                    </div>
                    <div class="w-16 text-right text-sm">
                        <p class="legenda">Visitas</p>
                        <p class="tabular-nums">{{ $c->atendimentos_comigo }}</p>
                    </div>
                    <x-icone nome="seta-dir" class="w-4 h-4 shrink-0 text-gray-300 group-hover:text-luna-brown" />
                </a>
            </li>
        @endforeach
    </ul>
    <div class="mt-4">{{ $clientes->links() }}</div>
@endif
@endsection
