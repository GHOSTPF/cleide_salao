<?php

namespace App\Policies;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isProfissional();
    }

    /** Profissional só vê clientes que já atendeu ou tem agendada. */
    public function view(User $user, Cliente $cliente): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isProfissional() && $user->profissional
            && Agendamento::where('cliente_id', $cliente->id)->where('profissional_id', $user->profissional->id)->exists();
    }

    /** Totais financeiros globais (total gasto, ticket médio): só admin. */
    public function verFinanceiro(User $user, ?Cliente $cliente = null): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->isAdmin();
    }

    public function bloquear(User $user, Cliente $cliente): bool
    {
        return $user->isAdmin();
    }

    public function mesclar(User $user): bool
    {
        return $user->isAdmin();
    }
}
