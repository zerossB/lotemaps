<?php

namespace App\Enums;

enum LotStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';

    public function label(): string
    {
        return match ($this) {
            LotStatus::Available => 'Available',
            LotStatus::Reserved => 'Reserved',
            LotStatus::Sold => 'Sold',
        };
    }

    public function color(): string
    {
        return match ($this) {
            LotStatus::Available => 'green',
            LotStatus::Reserved => 'yellow',
            LotStatus::Sold => 'red',
        };
    }
}
