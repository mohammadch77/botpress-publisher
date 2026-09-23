<?php

namespace App\Domain\Bot\Contracts;

interface BotDriverInterface
{
    public function sendMessage(string $chatId, string $text, array $options = []): array;

    public function sendPhoto(string $chatId, mixed $photo, array $options = []): array;

    public function sendVideo(string $chatId, mixed $video, array $options = []): array;

    public function sendDocument(string $chatId, mixed $document, array $options = []): array;

    public function editMessage(string $chatId, int $messageId, string $text, array $options = []): array;

    public function deleteMessage(string $chatId, int $messageId): bool;

    public function answerCallback(string $callbackQueryId, array $options = []): bool;

    public function getChat(string $chatId): array;

    public function setWebhook(string $url, array $options = []): bool;

    public function deleteWebhook(): bool;

    public function getMe(): array;
}
