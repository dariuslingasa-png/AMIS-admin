<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Finance = 'finance';
    case Staff = 'staff';
    case Student = 'student';

    public static function adminPortalValues(): array
    {
        return [
            self::Admin->value,
            self::Finance->value,
            self::Staff->value,
        ];
    }

    public static function paymentReviewerValues(): array
    {
        return [
            self::Admin->value,
            self::Finance->value,
        ];
    }
}
