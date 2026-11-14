<footer class="bg-luna-ink pb-10 text-sm text-white/60">
    <div class="mx-auto flex max-w-6xl flex-col gap-4 border-t border-white/15 px-4 pt-8 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="" class="h-8 w-8 rounded-full bg-white">
            <span class="font-serif text-lg text-white">{{ $config['nome_salao'] }}</span>
        </div>
        <p>&copy; {{ date('Y') }} · João Pessoa/PB · Seus dados são usados só para falar sobre seus agendamentos (LGPD).</p>
        <a href="{{ route('login') }}" class="text-white/60 hover:text-white">Área da equipe</a>
    </div>
</footer>
