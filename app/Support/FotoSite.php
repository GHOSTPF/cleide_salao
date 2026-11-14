<?php

namespace App\Support;

/**
 * Monta a URL das fotos do site definidas em config/fotos_site.php.
 * Aceita link do Unsplash (redimensionado pelo próprio Unsplash), outro link externo
 * ou caminho local dentro de public/ (ex.: "images/galeria/loiro.jpg").
 */
class FotoSite
{
    public static function url(string $origem, ?int $largura = null): string
    {
        if (str_starts_with($origem, 'https://images.unsplash.com/')) {
            return $origem . '?auto=format&fit=crop&q=70' . ($largura ? '&w=' . $largura : '');
        }

        return str_starts_with($origem, 'http') ? $origem : asset($origem);
    }

    /** srcset só faz sentido para o Unsplash, que redimensiona sob demanda. */
    public static function srcset(string $origem): ?string
    {
        if (! str_starts_with($origem, 'https://images.unsplash.com/')) {
            return null;
        }

        return collect([400, 600, 900, 1200, 1600])->map(fn ($w) => self::url($origem, $w) . " {$w}w")->implode(', ');
    }
}
