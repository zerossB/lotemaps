<?php

namespace App\Enums;

enum EmpreendimentoStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            EmpreendimentoStatus::Active => 'Active',
            EmpreendimentoStatus::Inactive => 'Inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            EmpreendimentoStatus::Active => 'green',
            EmpreendimentoStatus::Inactive => 'zinc',
        };
    }
}
