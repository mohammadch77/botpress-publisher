<?php

namespace App\Services\Bot;

use App\Domain\Bot\DTOs\IncomingCallback;
use App\Domain\Bot\DTOs\IncomingMessage;
use App\Domain\Bot\DTOs\IncomingUpdate;
use Illuminate\Support\Facades\Log;

class UpdateParser
{
    public function parseTelegram(array $payload): IncomingUpdate
    {
        return $this->parse('telegram', $payload);
    }

    public function parseBale(array $payload): IncomingUpdate
    {
        return $this->parse('bale', $payload);
    }

    private function parse(string $platform, array $payload): IncomingUpdate
    {
        $updateId = (int) ($payload['update_id'] ?? 0);

        if (isset($payload['callback_query'])) {
            return new IncomingUpdate(
                platform: $platform,
                updateType: 'callback_query',
                updateId: $updateId,
                callback: $this->parseCallback($payload['callback_query']),
            );
        }

        if (isset($payload['channel_post'])) {
            return new IncomingUpdate(
                platform: $platform,
                updateType: 'channel_post',
                updateId: $updateId,
                message: $this->parseMessage($payload['channel_post']),
            );
        }

        if (isset($payload['message'])) {
            return new IncomingUpdate(
                platform: $platform,
                updateType: 'message',
                updateId: $updateId,
                message: $this->parseMessage($payload['message']),
            );
        }

        if (isset($payload['edited_message'])) {
            return new IncomingUpdate(
                platform: $platform,
                updateType: 'edited_message',
                updateId: $updateId,
            );
        }

        Log::info('UpdateParser: unknown update type', [
            'platform' => $platform,
            'update_id' => $updateId,
            'keys' => array_keys($payload),
        ]);

        return new IncomingUpdate(
            platform: $platform,
            updateType: 'unknown',
            updateId: $updateId,
        );
    }

    private function parseMessage(array $message): IncomingMessage
    {
        [$messageType, $text, $caption, $mediaFileIds] = $this->resolveContent($message);

        return new IncomingMessage(
            chatId: (string) ($message['chat']['id'] ?? ''),
            chatType: $message['chat']['type'] ?? 'private',
            fromId: (string) ($message['from']['id'] ?? ''),
            fromUsername: $message['from']['username'] ?? null,
            fromFirstName: $message['from']['first_name'] ?? null,
            messageType: $messageType,
            text: $text,
            caption: $caption,
            mediaFileIds: $mediaFileIds,
            messageId: (int) ($message['message_id'] ?? 0),
            date: (int) ($message['date'] ?? 0),
        );
    }

    private function resolveContent(array $message): array
    {
        if (isset($message['text'])) {
            return ['text', $message['text'], null, null];
        }

        if (isset($message['photo'])) {
            $fileIds = array_map(fn (array $size) => $size['file_id'], $message['photo']);

            return ['photo', null, $message['caption'] ?? null, $fileIds];
        }

        if (isset($message['video'])) {
            return ['video', null, $message['caption'] ?? null, [$message['video']['file_id']]];
        }

        if (isset($message['document'])) {
            return ['document', null, $message['caption'] ?? null, [$message['document']['file_id']]];
        }

        if (isset($message['audio'])) {
            return ['audio', null, $message['caption'] ?? null, [$message['audio']['file_id']]];
        }

        return ['unknown', null, null, null];
    }

    private function parseCallback(array $callback): IncomingCallback
    {
        return new IncomingCallback(
            callbackId: (string) ($callback['id'] ?? ''),
            chatId: (string) ($callback['message']['chat']['id'] ?? ''),
            fromId: (string) ($callback['from']['id'] ?? ''),
            messageId: (int) ($callback['message']['message_id'] ?? 0),
            data: $callback['data'] ?? '',
        );
    }
}
