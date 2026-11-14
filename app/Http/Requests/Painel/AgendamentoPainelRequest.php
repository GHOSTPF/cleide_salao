<?php

namespace App\Http\Requests\Painel;

use App\Rules\TelefoneValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendamentoPainelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'nome' => ['required_without:cliente_id', 'nullable', 'string', 'min:3', 'max:120'],
            'telefone' => ['required_without:cliente_id', 'nullable', 'string', 'max:25', new TelefoneValido],
            'servico_id' => ['required', 'integer', Rule::exists('servicos', 'id')->where('ativo', true)],
            'profissional_id' => ['required', 'integer', Rule::exists('profissionais', 'id')->where('ativo', true)],
            'data' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'date_format:H:i'],
            'origem' => ['required', Rule::in(['painel', 'whatsapp'])],
            'encaixe' => ['nullable', 'boolean'],
            'confirmar_encaixe' => ['required_if:encaixe,1', 'nullable', 'accepted_if:encaixe,1'],
            'observacoes_cliente' => ['nullable', 'string', 'max:500'],
            'observacoes_internas' => ['nullable', 'string', 'max:2000'],
            'lista_espera_id' => ['nullable', 'integer', 'exists:lista_espera,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required_without' => 'Selecione uma cliente ou informe nome e WhatsApp.',
            'telefone.required_without' => 'Selecione uma cliente ou informe nome e WhatsApp.',
            'hora.required' => 'Escolha o horário.',
            'confirmar_encaixe.required_if' => 'Confirme que deseja encaixar fora do expediente.',
            'confirmar_encaixe.accepted_if' => 'Confirme que deseja encaixar fora do expediente.',
        ];
    }
}
