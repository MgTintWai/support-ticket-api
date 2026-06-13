<?php

namespace App\Enums;

enum SlaStatus: string
{
    case OnTrack = 'on_track';
    case DueSoon = 'due_soon';
    case Overdue = 'overdue';
    case Met = 'met';
    case Breached = 'breached';
}
