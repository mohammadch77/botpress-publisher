<?php

namespace App\Domain\Bot\Contracts;

use App\Domain\Bot\DTOs\BotChat;
use App\Domain\Bot\DTOs\BotInfo;
use App\Domain\Bot\DTOs\BotResponse;

interface BotDriverInterface
{
    public function sendMessage(string $chatId, string $text, array $options = []): BotResponse;

    public function sendPhoto(string $chatId, mixed $photo, string $caption = '', array $options = []): BotResponse;

    public function sendVideo(string $chatId, mixed $video, string $caption = '', array $options = []): BotResponse;

    public function sendDocument(string $chatId, mixed $document, string $caption = '', array $options = []): BotResponse;

    public function editMessage(string $chatId, int $messageId, string $text, array $options = []): BotResponse;

    public function deleteMessage(string $chatId, int $messageId): bool;

    public function answerCallback(string $callbackQueryId, string $text = '', bool $showAlert = false): bool;

    public function getChat(string $chatId): BotChat;

    public function setWebhook(string $url, array $options = []): bool;

    public function deleteWebhook(): bool;

    public function getMe(): BotInfo;
}
