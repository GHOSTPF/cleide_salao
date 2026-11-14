<form method="POST" action="{{ $acao }}" class="grid gap-3 sm:grid-cols-2">
    @csrf
    <x-input nome="data_inicio" rotulo="De (data)" tipo="date" :valor="today()->toDateString()" required />
    <x-input nome="hora_inicio" rotulo="Hora (opcional)" tipo="time" />
    <x-input nome="data_fim" rotulo="Até (data)" tipo="date" :valor="today()->toDateString()" required />
    <x-input nome="hora_fim" rotulo="Hora (opcional)" tipo="time" />
    <div class="sm:col-span-2"><x-input nome="motivo" rotulo="Motivo" placeholder="Ex.: folga, consulta médica" /></div>
    <p class="text-xs text-gray-600 sm:col-span-2">Sem horas, o bloqueio vale para o(s) dia(s) inteiro(s).</p>
    <div class="sm:col-span-2"><x-button tamanho="sm" icone="cadeado">Bloquear agenda</x-button></div>
</form>
