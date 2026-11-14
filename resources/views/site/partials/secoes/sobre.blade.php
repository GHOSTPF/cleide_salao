<section id="sobre" class="py-20 sm:py-28">
    <div class="revelar mx-auto grid max-w-6xl items-center gap-12 px-4 sm:px-6 md:grid-cols-2 md:gap-16" x-data x-intersect.once="$el.classList.add('visivel')">
        <div class="order-2 md:order-1">
            <div class="aspect-[4/5] max-w-md overflow-hidden rounded-lg bg-luna-sand">
                <x-foto-site chave="sobre" :galeria="$galeria" class="h-full w-full object-cover" sizes="(min-width: 768px) 28rem, 100vw" />
            </div>
            {{-- Só afirma que é o espaço do salão quando a foto é real --}}
            @if ($galeria->contains(fn ($f) => $f->categoria === 'ambiente' && ! str_contains($f->url, '/placeholders/')))
                <p class="mt-3 text-sm text-gray-500">Nosso espaço em João Pessoa, Paraíba.</p>
            @endif
        </div>
        <div class="order-1 md:order-2">
            <p class="sobretitulo">Sobre nós</p>
            <h2 class="titulo-secao mt-2">Beleza feita com calma e cuidado</h2>
            <p class="mt-6 leading-relaxed text-gray-700">{{ $config['sobre'] }}</p>
            <dl class="mt-8 space-y-5 border-l border-luna-nude pl-5 text-sm">
                <div>
                    <dt class="font-medium">Loiros com saúde</dt>
                    <dd class="mt-0.5 text-gray-600">Descoloração e mechas pensadas para preservar a fibra do fio.</dd>
                </div>
                <div>
                    <dt class="font-medium">Atendimento sem pressa</dt>
                    <dd class="mt-0.5 text-gray-600">Cada horário é planejado para você ser atendida com atenção.</dd>
                </div>
                <div>
                    <dt class="font-medium">Produtos profissionais</dt>
                    <dd class="mt-0.5 text-gray-600">Técnicas atualizadas e marcas em que confiamos.</dd>
                </div>
            </dl>
        </div>
    </div>
</section>
