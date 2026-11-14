<?php

/*
 * Fotos do site público.
 *
 * Cada "url" pode ser:
 *   - um link do Unsplash (images.unsplash.com/photo-...): carregado direto do Unsplash;
 *   - um caminho dentro de public/ (ex.: 'images/galeria/loiro-1.jpg'): para fotos próprias.
 *
 * Unsplash: licença gratuita, uso comercial permitido, crédito opcional (https://unsplash.com/license).
 * "fonte" guarda a página original de cada foto, caso queiram dar crédito.
 *
 * Depois de alterar este arquivo em produção, rode: php artisan config:clear
 */
return [

    // Foto do topo. Uma foto real da categoria "loiros" enviada pelo painel tem prioridade.
    'hero' => [
        'categoria' => 'loiros',
        'url' => 'https://images.unsplash.com/photo-1793328556262-79fab6efd759',
        'alt' => 'Cabelo loiro longo e ondulado visto de costas',
        'fonte' => 'https://unsplash.com/photos/person-with-long-wavy-blonde-hair-_0acJIzFw-0',
    ],

    // Foto do "Sobre nós". Uma foto real da categoria "ambiente" enviada pelo painel tem prioridade.
    'sobre' => [
        'categoria' => 'ambiente',
        'url' => 'https://images.unsplash.com/photo-1695527081827-fdbc4e77be9b',
        'alt' => 'Sala de salão de beleza clara, com planta, cadeira de lavatório e pufes de palha',
        'fonte' => 'https://unsplash.com/photos/a-room-with-a-chair-sink-and-a-plant-in-it-Ui6DZ9A1eXU',
    ],

    // Galeria "Trabalhos recentes", na ordem em que aparecem.
    // categoria: loiros, cortes, unhas, maquiagem, sobrancelhas ou ambiente (vira o filtro da galeria).
    'galeria' => [
        [
            'categoria' => 'loiros',
            'titulo' => 'Loiro e morena iluminados',
            'url' => 'https://images.unsplash.com/photo-1785456283212-338f5bc44d50',
            'fonte' => 'https://unsplash.com/photos/two-women-with-long-wavy-blonde-and-brunette-hair-RpFQdgJ9gQw',
        ],
        [
            'categoria' => 'cortes',
            'titulo' => 'Corte em cabelo loiro',
            'url' => 'https://images.unsplash.com/photo-1792679430411-5781a737d315',
            'fonte' => 'https://unsplash.com/photos/stylist-trimming-long-blonde-hair-skpo8RG3UnA',
        ],
        [
            'categoria' => 'unhas',
            'titulo' => 'Unhas nude',
            'url' => 'https://images.unsplash.com/photo-1610992015762-45dca7fa3a85',
            'fonte' => 'https://unsplash.com/photos/hand-with-light-pink-manicured-nails-vtQHwU4F13s',
        ],
        [
            'categoria' => 'maquiagem',
            'titulo' => 'Make para evento',
            'url' => 'https://images.unsplash.com/photo-1717177007172-d333206f67d6',
            'fonte' => 'https://unsplash.com/photos/a-woman-is-getting-her-make-up-done-XobxJRY7lgk',
        ],
        [
            'categoria' => 'sobrancelhas',
            'titulo' => 'Design de sobrancelhas com pinça',
            'url' => 'https://images.unsplash.com/photo-1792679430669-caa0d70a9c52',
            'fonte' => 'https://unsplash.com/photos/woman-getting-eyebrows-shaped-with-tweezers--k0_8bZ4L8c',
        ],
        [
            'categoria' => 'unhas',
            'titulo' => 'Francesinha',
            'url' => 'https://images.unsplash.com/photo-1762373349045-c2decd4ec3f3',
            'fonte' => 'https://unsplash.com/photos/womans-hand-with-french-manicure-holds-small-flower-3AQ_VHXfjEg',
        ],
        [
            'categoria' => 'sobrancelhas',
            'titulo' => 'Sobrancelhas alinhadas',
            'url' => 'https://images.unsplash.com/photo-1783013951101-2d9ed3eac234',
            'fonte' => 'https://unsplash.com/photos/hands-groom-a-womans-eyebrow-with-a-styling-tool-RVGoYa7fIVI',
        ],
        [
            'categoria' => 'ambiente',
            'titulo' => 'Atendimento no salão',
            'url' => 'https://images.unsplash.com/photo-1695527081848-1e46c06e6458',
            'fonte' => 'https://unsplash.com/photos/a-woman-getting-her-hair-done-in-a-salon-PnDr2j28gXA',
        ],
    ],
];
