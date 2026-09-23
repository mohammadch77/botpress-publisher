<?php

namespace App\Enums;

enum ContentAssetRole: string
{
    case Featured = 'featured';
    case Inline = 'inline';
    case Attachment = 'attachment';
    case Gallery = 'gallery';
}
