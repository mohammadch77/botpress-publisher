<?php

namespace App\Enums;

enum DiscoveryStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
