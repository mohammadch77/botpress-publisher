<?php

namespace App\Enums;

enum PostStatus: string
{
    case Publish = 'publish';
    case Draft = 'draft';
    case Pending = 'pending';
    case Private = 'private';
    case Future = 'future';
}
