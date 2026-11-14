<?php

namespace Tests\Unit;

use App\Support\Telefone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TelefoneTest extends TestCase
{
    public static function formatos(): array
    {
        return [
            'mascara' => ['(83) 99999-9999'],
            'so digitos' => ['83999999999'],
            'com ddi e mais' => ['+55 83 99999-9999'],
            'com ddi sem mais' => ['5583999999999'],
            'com zero de operadora' => ['083 99999 9999'],
        ];
    }

    #[DataProvider('formatos')]
    public function test_normaliza_formatos_diferentes_para_o_mesmo_numero(string $entrada): void
    {
        $this->assertSame('5583999999999', Telefone::normalizar($entrada));
    }

    public function test_valida_e_formata(): void
    {
        $this->assertTrue(Telefone::valido('(83) 98666-9294'));
        $this->assertFalse(Telefone::valido('1234'));
        $this->assertSame('(83) 98666-9294', Telefone::formatar('5583986669294'));
    }
}
