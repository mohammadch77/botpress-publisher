<?php

namespace App\Enums;

enum ParseMode: string
{
    case Html = 'HTML';
    case Markdown = 'Markdown';
    case MarkdownV2 = 'MarkdownV2';
}
