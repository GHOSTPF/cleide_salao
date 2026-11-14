<?php

namespace App\Http\Requests\Painel;

use App\Services\ReagendamentoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReagendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reagendar', $this->route('agendamento'));
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'date_format:H:i'],
            'profissional_id' => ['nullable', 'integer', 'exists:profissionais,id'],
            'pedido_por' => ['required', Rule::in(array_keys(ReagendamentoService::PEDIDO_POR))],
            'motivo' => ['nullable', 'string', 'max:255'],
            'encaixe' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['hora.required' => 'Escolha o novo horário.'];
    }
}
