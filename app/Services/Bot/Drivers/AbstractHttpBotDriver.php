<?php

namespace App\Services\Bot\Drivers;

use App\Domain\Bot\Contracts\BotDriverInterface;
use App\Domain\Bot\DTOs\BotChat;
use App\Domain\Bot\DTOs\BotInfo;
use App\Domain\Bot\DTOs\BotResponse;
use Illuminate\Support\Facades\Http;

abstract class AbstractHttpBotDriver implements BotDriverInterface
{
    protected string $baseUrl;

    public function __construct(
        private readonly string $token,
    ) {}

    public function sendMessage(string $chatId, string $text, array $options = []): BotResponse
    {
        return $this->toResponse($this->call('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $options['parse_mode'] ?? 'HTML',
        ], $options)));
    }

    public function sendPhoto(string $chatId, mixed $photo, string $caption = '', array $options = []): BotResponse
    {
        return $this->toResponse($this->call('sendPhoto', array_merge([
            'chat_id' => $chatId,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => $options['parse_mode'] ?? 'HTML',
        ], $options)));
    }

    public function sendVideo(string $chatId, mixed $video, string $caption = '', array $options = []): BotResponse
    {
        return $this->toResponse($this->call('sendVideo', array_merge([
            'chat_id' => $chatId,
            'video' => $video,
            'caption' => $caption,
            'parse_mode' => $options['parse_mode'] ?? 'HTML',
        ], $options)));
    }

    public function sendDocument(string $chatId, mixed $document, string $caption = '', array $options = []): BotResponse
    {
        return $this->toResponse($this->call('sendDocument', array_merge([
            'chat_id' => $chatId,
            'document' => $document,
            'caption' => $caption,
            'parse_mode' => $options['parse_mode'] ?? 'HTML',
        ], $options)));
    }

    public function editMessage(string $chatId, int $messageId, string $text, array $options = []): BotResponse
    {
        return $this->toResponse($this->call('editMessageText', array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $options['parse_mode'] ?? 'HTML',
        ], $options)));
    }

    public function deleteMessage(string $chatId, int $messageId): bool
    {
        $result = $this->call('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);

        return (bool) ($result['ok'] ?? false);
    }

    public function answerCallback(string $callbackQueryId, string $text = '', bool $showAlert = false): bool
    {
        $result = $this->call('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert,
        ]);

        return (bool) ($result['ok'] ?? false);
    }

    public function getChat(string $chatId): BotChat
    {
        $result = $this->call('getChat', ['chat_id' => $chatId]);
        $chat = $result['result'] ?? [];

        return new BotChat(
            id: (string) ($chat['id'] ?? $chatId),
            type: $chat['type'] ?? 'private',
            title: $chat['title'] ?? null,
            username: $chat['username'] ?? null,
            memberCount: $chat['member_count'] ?? null,
        );
    }

    public function setWebhook(string $url, array $options = []): bool
    {
        $result = $this->call('setWebhook', array_merge(['url' => $url], $options));

        return (bool) ($result['ok'] ?? false);
    }

    public function deleteWebhook(): bool
    {
        $result = $this->call('deleteWebhook');

        return (bool) ($result['ok'] ?? false);
    }

    public function getMe(): BotInfo
    {
        $result = $this->call('getMe');
        $info = $result['result'] ?? [];

        if (! ($result['ok'] ?? false)) {
            throw new \RuntimeException($result['description'] ?? 'getMe failed');
        }

        return new BotInfo(
            id: (int) ($info['id'] ?? 0),
            username: $info['username'] ?? '',
            firstName: $info['first_name'] ?? '',
            canJoinGroups: (bool) ($info['can_join_groups'] ?? false),
            canReadAllGroupMessages: (bool) ($info['can_read_all_group_messages'] ?? false),
        );
    }

    private function toResponse(array $result): BotResponse
    {
        if (! ($result['ok'] ?? false)) {
            return new BotResponse(
                ok: false,
                raw: $result,
                errorDescription: $result['description'] ?? null,
                errorCode: $result['error_code'] ?? null,
            );
        }

        $message = $result['result'] ?? [];

        return new BotResponse(
            ok: true,
            messageId: $message['message_id'] ?? null,
            chatId: isset($message['chat']['id']) ? (string) $message['chat']['id'] : null,
            raw: $result,
        );
    }

    protected function call(string $method, array $params = []): array
    {
        try {
            $response = Http::post($this->baseUrl.$this->token.'/'.$method, $params);

            $decoded = $response->json();

            return is_array($decoded) ? $decoded : ['ok' => false, 'description' => 'Invalid response'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => 'Request failed'];
        }
    }
}
