<?php

namespace App\Enums;

enum StatusAgendamento: string
{
    case Pendente = 'pendente';
    case Confirmado = 'confirmado';
    case Concluido = 'concluido';
    case Cancelado = 'cancelado';
    case NaoCompareceu = 'nao_compareceu';
    case Reagendado = 'reagendado';

    /** Status que ocupam a agenda (mesmos da exclusion constraint). */
    public static function ativos(): array
    {
        return [self::Pendente->value, self::Confirmado->value, self::Concluido->value];
    }

    /** Transições "normais" de fluxo. Estorno e desfazer falta são operações especiais do admin. */
    public function podeIrPara(self $novo): bool
    {
        return in_array($novo, match ($this) {
            self::Pendente => [self::Confirmado, self::Cancelado, self::Reagendado, self::Concluido],
            self::Confirmado => [self::Cancelado, self::Reagendado, self::NaoCompareceu, self::Concluido],
            self::Concluido => [self::Confirmado], // estorno (só admin)
            self::NaoCompareceu => [self::Confirmado], // desfazer falta (só admin)
            self::Cancelado, self::Reagendado => [],
        }, true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Confirmado => 'Confirmado',
            self::Concluido => 'Concluído',
            self::Cancelado => 'Cancelado',
            self::NaoCompareceu => 'Não compareceu',
            self::Reagendado => 'Reagendado',
        };
    }

    /** Classes Tailwind do selo de status. */
    public function classes(): string
    {
        return match ($this) {
            self::Pendente => 'bg-amber-100 text-amber-900 border-amber-400',
            self::Confirmado => 'bg-sky-100 text-sky-900 border-sky-500',
            self::Concluido => 'bg-emerald-100 text-emerald-900 border-emerald-500',
            self::Cancelado => 'bg-gray-100 text-gray-700 border-gray-400',
            self::NaoCompareceu => 'bg-red-100 text-red-900 border-red-500',
            self::Reagendado => 'bg-violet-100 text-violet-900 border-violet-400',
        };
    }

    public function corBorda(): string
    {
        return match ($this) {
            self::Pendente => '#d97706',
            self::Confirmado => '#0284c7',
            self::Concluido => '#059669',
            self::Cancelado => '#9ca3af',
            self::NaoCompareceu => '#dc2626',
            self::Reagendado => '#7c3aed',
        };
    }
}
