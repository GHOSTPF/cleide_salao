<?php

namespace App\Http\Requests\Site;

use App\Rules\TelefoneValido;
use App\Support\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendarRequest extends FormRequest
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
            'hora' => ['required', 'date_format:H:i'],
            'observacoes' => ['nullable', 'string', 'max:500'],
            'consentimento' => ['accepted'],
            'website' => ['nullable', 'max:0'], // honeypot
        ];
    }

    public function messages(): array
    {
        return [
            'nome.regex' => 'Use apenas letras no nome.',
            'hora.required' => 'Escolha um horário.',
            'consentimento.accepted' => 'Para agendar, autorize o uso dos seus dados para contato.',
            'data.after_or_equal' => 'Escolha uma data a partir de hoje.',
            'data.before_or_equal' => 'Agendamentos podem ser feitos com até ' . Setting::int('dias_maximos_agendamento') . ' dias de antecedência.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return url('/') . '#agendar';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'profissional_id' => $this->input('profissional_id') ?: null,
            'nome' => is_string($this->input('nome')) ? trim($this->input('nome')) : $this->input('nome'),
        ]);
    }
}
