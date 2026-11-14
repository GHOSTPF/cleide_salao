<?php

namespace App\Policies;

use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\User;
use App\Support\Setting;

class AgendamentoPolicy
{
    /** Profissional só acessa os próprios agendamentos. */
    private function dona(User $user, Agendamento $ag): bool
    {
        return $user->isProfissional() && $user->profissional && $ag->profissional_id === $user->profissional->id;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isProfissional();
    }

    public function view(User $user, Agendamento $ag): bool
    {
        return $user->isAdmin() || $this->dona($user, $ag);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Agendamento $ag): bool
    {
        return $user->isAdmin() || $this->dona($user, $ag);
    }

    public function confirmar(User $user, Agendamento $ag): bool
    {
        return $this->update($user, $ag) && $ag->status === StatusAgendamento::Pendente;
    }

    public function concluir(User $user, Agendamento $ag): bool
    {
        return $this->update($user, $ag);
    }

    public function marcarFalta(User $user, Agendamento $ag): bool
    {
        return $this->update($user, $ag);
    }

    public function reagendar(User $user, Agendamento $ag): bool
    {
        return $user->isAdmin() || ($this->dona($user, $ag) && Setting::bool('profissional_pode_reagendar'));
    }

    public function cancelar(User $user, Agendamento $ag): bool
    {
        return $user->isAdmin() || ($this->dona($user, $ag) && Setting::bool('profissional_pode_cancelar'));
    }

    public function estornar(User $user, Agendamento $ag): bool
    {
        return $user->isAdmin();
    }

    public function desfazerFalta(User $user, Agendamento $ag): bool
    {
        return $user->isAdmin();
    }
}
