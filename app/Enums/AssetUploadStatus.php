<?php

namespace App\Enums;

enum AssetUploadStatus: string
{
    case Pending = 'pending';
    case Uploaded = 'uploaded';
    case Failed = 'failed';
}
