<?php

namespace App\Services\Bot\Drivers;

class TelegramBotDriver extends AbstractHttpBotDriver
{
    protected string $baseUrl = 'https://api.telegram.org/bot';
}
