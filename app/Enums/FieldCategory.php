<?php

namespace App\Enums;

enum FieldCategory: string
{
    case Basic = 'basic';
    case Choice = 'choice';
    case Advanced = 'advanced';
    case Layout = 'layout';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Basic',
            self::Choice => 'Choice',
            self::Advanced => 'Advanced',
            self::Layout => 'Layout',
        };
    }
}
