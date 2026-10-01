<?php

namespace App\Enums;

enum UserRole: string
{
    case Guest = 'guest';
    case Citizen = 'citizen';
    case Secretary = 'secretary';
    case Treasurer = 'treasurer';
    case Security = 'security';
    case RtHead = 'rt_head';
    case Admin = 'admin';
}
