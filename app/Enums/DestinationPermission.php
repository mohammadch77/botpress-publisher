<?php

namespace App\Enums;

enum DestinationPermission: string
{
    case View = 'view';
    case Publish = 'publish';
    case Manage = 'manage';
}
