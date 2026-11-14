<form method="GET" class="flex flex-wrap items-end gap-2">
    @foreach ($extras ?? [] as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
    <div><label class="rotulo" for="de">De</label><input type="date" id="de" name="de" value="{{ $de->toDateString() }}" class="campo"></div>
    <div><label class="rotulo" for="ate">Até</label><input type="date" id="ate" name="ate" value="{{ $ate->toDateString() }}" class="campo"></div>
    {{ $slot ?? '' }}
    <x-button icone="busca">Aplicar</x-button>
    <div class="flex gap-1 text-xs">
        <a class="pilula bg-white" href="?de={{ now()->startOfMonth()->toDateString() }}&ate={{ now()->endOfMonth()->toDateString() }}">Este mês</a>
        <a class="pilula bg-white" href="?de={{ now()->subMonthNoOverflow()->startOfMonth()->toDateString() }}&ate={{ now()->subMonthNoOverflow()->endOfMonth()->toDateString() }}">Mês passado</a>
        <a class="pilula bg-white" href="?de={{ today()->toDateString() }}&ate={{ today()->toDateString() }}">Hoje</a>
    </div>
</form>
