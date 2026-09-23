<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Approved = 'approved';
    case Archived = 'archived';
}
