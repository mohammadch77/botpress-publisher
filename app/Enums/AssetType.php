<?php

namespace App\Enums;

enum AssetType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Document = 'document';
    case Other = 'other';
}
