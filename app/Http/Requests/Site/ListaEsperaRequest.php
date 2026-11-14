<?php

namespace App\Http\Requests\Site;

use App\Rules\TelefoneValido;
use App\Support\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListaEsperaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\pL\s\'\.-]+$/u'],
            'telefone' => ['required', 'string', 'max:25', new TelefoneValido],
            'servico_id' => ['required', 'integer', Rule::exists('servicos', 'id')->where('ativo', true)->where('exibir_no_site', true)],
            'profissional_id' => ['nullable', 'integer', Rule::exists('profissionais', 'id')->where('ativo', true)],
            'data' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:' . today()->addDays(Setting::int('dias_maximos_agendamento'))->toDateString()],
            'periodo_preferido' => ['required', Rule::in(['manha', 'tarde', 'qualquer'])],
            'consentimento' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return ['consentimento.accepted' => 'Para entrar na lista de espera, autorize o uso dos seus dados para contato.'];
    }

    protected function getRedirectUrl(): string
    {
        return url('/') . '#agendar';
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['profissional_id' => $this->input('profissional_id') ?: null]);
    }
}
