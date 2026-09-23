<?php

namespace App\Enums;

enum DestinationType: string
{
    case WordPressSite = 'wordpress_site';
    case TelegramChannel = 'telegram_channel';
    case TelegramGroup = 'telegram_group';
    case BaleChannel = 'bale_channel';
    case BaleGroup = 'bale_group';
}
