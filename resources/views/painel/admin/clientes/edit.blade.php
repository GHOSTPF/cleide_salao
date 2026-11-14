@extends('painel.layout')
@section('titulo', 'Editar cliente')

@section('conteudo')
<x-card class="max-w-2xl">
    <form method="POST" action="{{ route('admin.clientes.update', $cliente) }}" class="grid gap-4 sm:grid-cols-2">
        @csrf @method('PUT')
        <div class="sm:col-span-2"><x-input nome="nome" rotulo="Nome" :valor="$cliente->nome" required /></div>
        <x-input nome="telefone" rotulo="WhatsApp" :valor="$cliente->telefone_formatado" x-data x-mask="(99) 99999-9999" required />
        <x-input nome="email" rotulo="E-mail" tipo="email" :valor="$cliente->email" />
        <x-input nome="data_nascimento" rotulo="Data de nascimento" tipo="date" :valor="$cliente->data_nascimento?->toDateString()" />
        <x-input nome="instagram" rotulo="Instagram" :valor="$cliente->instagram" placeholder="@usuario" />
        <x-select nome="origem" rotulo="Origem" :valor="$cliente->origem->value"
                  :opcoes="collect(\App\Enums\OrigemCliente::cases())->mapWithKeys(fn ($o) => [$o->value => $o->label()])" />
        <x-select nome="profissional_preferida_id" rotulo="Profissional preferida" vazio="Nenhuma" :valor="$cliente->profissional_preferida_id" :opcoes="$profissionais" />
        <div class="sm:col-span-2"><x-input nome="observacoes" rotulo="Observações (alergias, preferências)" tipo="textarea" :valor="$cliente->observacoes" /></div>
        <div class="flex gap-2 sm:col-span-2">
            <a href="{{ route('admin.clientes.show', $cliente) }}" class="btn-secundario">Voltar</a>
            <x-button>Salvar</x-button>
        </div>
    </form>
</x-card>
@endsection
