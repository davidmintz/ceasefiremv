<?php

namespace App\Enum;

enum AttendanceMode: string
{
    case InPerson = 'in_person';
    case Zoom = 'zoom';

    public function label(): string
    {
        return match ($this) {
            self::InPerson => 'in person',
            self::Zoom => 'via Zoom',
        };
    }
}
