<?php

namespace App\Http\Requests\Painel;

use App\Enums\FormaPagamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConcluirAtendimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('concluir', $this->route('agendamento'));
    }

    public function rules(): array
    {
        return [
            'valor_cobrado' => ['required', 'regex:/^\d{1,7}([.,]\d{1,2})?$/'],
            'pagamentos' => ['required', 'array', 'min:1', 'max:2'],
            'pagamentos.*.forma' => ['required', Rule::enum(FormaPagamento::class)],
            'pagamentos.*.valor' => ['required', 'regex:/^\d{1,7}([.,]\d{1,2})?$/'],
            'anotacao' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'valor_cobrado.regex' => 'Informe o valor no formato 150,00.',
            'pagamentos.*.valor.regex' => 'Informe o valor do pagamento no formato 150,00.',
        ];
    }
}
