<?php

namespace App\Enums;

enum DestinationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Error = 'error';
    case Pending = 'pending';
}
