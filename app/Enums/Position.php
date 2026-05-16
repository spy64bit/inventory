<?php

namespace App\Enums;

enum Position: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Staff = 'staff';
}
