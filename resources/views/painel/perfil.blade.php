@extends('painel.layout')
@section('titulo', 'Meu perfil')

@section('conteudo')
<div class="grid max-w-4xl gap-6 md:grid-cols-2">
    <x-card titulo="Dados de acesso">
        <form method="POST" action="{{ route('perfil.update') }}" class="space-y-4">
            @csrf @method('PATCH')
            <x-input nome="nome" rotulo="Nome" :valor="$user->nome" required />
            <x-input nome="email" rotulo="E-mail" tipo="email" :valor="$user->email" required />
            <x-button>Salvar</x-button>
        </form>
    </x-card>

    <x-card titulo="Alterar senha">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label for="current_password" class="rotulo">Senha atual</label>
                <input type="password" name="current_password" id="current_password" class="campo" autocomplete="current-password">
                @foreach ($errors->updatePassword->get('current_password') as $m)<p class="mt-1 text-sm text-red-700">{{ $m }}</p>@endforeach
            </div>
            <div>
                <label for="password" class="rotulo">Nova senha</label>
                <input type="password" name="password" id="password" class="campo" autocomplete="new-password">
                @foreach ($errors->updatePassword->get('password') as $m)<p class="mt-1 text-sm text-red-700">{{ $m }}</p>@endforeach
            </div>
            <div>
                <label for="password_confirmation" class="rotulo">Confirmar nova senha</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="campo" autocomplete="new-password">
            </div>
            <x-button>Alterar senha</x-button>
            @if (session('status') === 'password-updated')
                <p class="text-sm text-emerald-700">Senha alterada.</p>
            @endif
        </form>
    </x-card>
</div>
@endsection
