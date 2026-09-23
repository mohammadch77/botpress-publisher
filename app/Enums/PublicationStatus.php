<?php

namespace App\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case Processing = 'processing';
    case Published = 'published';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Partial = 'partial';
}
