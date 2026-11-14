<header x-data="{ aberto: false, rolou: false }" @scroll.window="rolou = window.scrollY > 40"
        :class="rolou ? 'bg-luna-cream/95 border-luna-sand backdrop-blur' : 'bg-transparent border-transparent'"
        class="fixed inset-x-0 top-0 z-40 border-b transition-colors">
    <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6" aria-label="Principal">
        <a href="#inicio" class="flex items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9 rounded-full object-cover">
            <span class="font-serif text-xl">{{ $config['nome_salao'] }}</span>
        </a>
        <div class="hidden items-center gap-7 text-sm md:flex">
            <a href="#servicos" class="text-gray-700 hover:text-luna-ink">Serviços</a>
            <a href="#galeria" class="text-gray-700 hover:text-luna-ink">Trabalhos</a>
            <a href="#equipe" class="text-gray-700 hover:text-luna-ink">Equipe</a>
            <a href="#contato" class="text-gray-700 hover:text-luna-ink">Contato</a>
            <a href="#agendar" class="btn-primario px-4 py-2">Agendar</a>
        </div>
        <button type="button" class="-mr-1 rounded-md p-2 md:hidden" @click="aberto = !aberto" :aria-expanded="aberto.toString()" aria-label="Abrir menu">
            <x-icone nome="menu" class="w-6 h-6" />
        </button>
    </nav>
    <div x-show="aberto" x-cloak x-transition.opacity @click.outside="aberto = false"
         class="border-t border-luna-sand bg-luna-cream px-4 pb-4 md:hidden">
        <a href="#servicos" @click="aberto = false" class="block border-b border-luna-sand py-3">Serviços</a>
        <a href="#galeria" @click="aberto = false" class="block border-b border-luna-sand py-3">Trabalhos</a>
        <a href="#equipe" @click="aberto = false" class="block border-b border-luna-sand py-3">Equipe</a>
        <a href="#contato" @click="aberto = false" class="block py-3">Contato</a>
        <a href="#agendar" @click="aberto = false" class="btn-primario mt-2 w-full py-3">Agendar horário</a>
    </div>
</header>
