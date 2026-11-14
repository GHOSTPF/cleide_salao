<?php

namespace App\Rules;

use App\Support\Telefone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TelefoneValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Telefone::valido((string) $value)) {
            $fail('Informe um WhatsApp válido com DDD, ex.: (83) 99999-9999.');
        }
    }
}
