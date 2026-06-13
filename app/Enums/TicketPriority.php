<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function slaHours(): int
    {
        return match ($this) {
            self::Low => 72,
            self::Medium => 48,
            self::High => 24,
        };
    }

    public function dueSoonHours(): int
    {
        return (int) max(2, floor($this->slaHours() * 0.25));
    }
}
