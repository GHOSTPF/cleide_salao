@extends('painel.layout')
@section('titulo', 'Meus bloqueios')

@section('conteudo')
<p class="mb-6 max-w-2xl text-sm text-gray-600">Bloqueie horários em que você não pode atender — folga, consulta, compromisso. Nesses períodos o site não oferece horários com você.</p>

<div class="grid gap-8 lg:grid-cols-5">
    <x-card titulo="Novo bloqueio" class="lg:col-span-3">
        @include('painel.partials.form-bloqueio', ['acao' => route('profissional.bloqueios.store')])
    </x-card>

    <div class="space-y-8 lg:col-span-2">
        <section>
            <h2 class="mb-2 text-sm font-semibold">Seus bloqueios</h2>
            @include('painel.partials.lista-bloqueios', ['bloqueios' => $bloqueios, 'rotaExcluir' => 'profissional.bloqueios.destroy'])
        </section>
        @if ($fechamentos->isNotEmpty())
            <section>
                <h2 class="mb-2 text-sm font-semibold">Salão fechado</h2>
                <ul class="divide-y divide-luna-sand/70 text-sm">
                    @foreach ($fechamentos as $f)
                        <li class="py-2.5">
                            <span class="font-medium tabular-nums">{{ $f->inicio->format('d/m/Y') }}{{ $f->inicio->isSameDay($f->fim) ? '' : ' – ' . $f->fim->format('d/m/Y') }}</span>
                            <span class="text-gray-600"> · {{ $f->motivo }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</div>
@endsection
