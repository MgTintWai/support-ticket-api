<?php

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';
    case SupportAgent = 'support_agent';

    public function isAgent(): bool
    {
        return $this === self::SupportAgent;
    }

    public function isClient(): bool
    {
        return $this === self::Client;
    }
}
