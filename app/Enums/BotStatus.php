<?php

namespace App\Enums;

enum BotStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Error = 'error';
    case Pending = 'pending';
}
