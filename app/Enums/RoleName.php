<?php

namespace App\Enums;

enum RoleName: string
{
    case ADMIN = 'admin';
    case CUSTOMER = 'customer';
    case MANAGER = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'مدیر کل',
            self::CUSTOMER => 'مشتری',
            self::MANAGER => 'مدیر سیستم',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? (string) $value;
    }
}
