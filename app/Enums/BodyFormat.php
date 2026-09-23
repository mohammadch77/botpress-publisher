<?php

namespace App\Enums;

enum BodyFormat: string
{
    case Html = 'html';
    case Markdown = 'markdown';
    case Plain = 'plain';
}
