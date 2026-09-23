<?php

namespace App\Enums;

enum ContentType: string
{
    case Article = 'article';
    case Note = 'note';
    case MediaPost = 'media_post';
    case Thread = 'thread';
}
