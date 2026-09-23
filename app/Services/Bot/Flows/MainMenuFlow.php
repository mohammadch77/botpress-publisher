<?php

namespace App\Services\Bot\Flows;

use App\Domain\Bot\Contracts\FlowHandlerInterface;
use App\Domain\Bot\DTOs\IncomingUpdate;
use App\Models\ConversationSession;
use App\Services\Bot\BotDriverFactory;

class MainMenuFlow implements FlowHandlerInterface
{
    public function __construct(
        private readonly BotDriverFactory $driverFactory,
    ) {}

    public function handle(ConversationSession $session, IncomingUpdate $update): void
    {
        $bot = $session->bot;
        $driver = $this->driverFactory->make($bot);
        $chatId = $update->message?->chatId ?? $update->callback?->chatId;

        $driver->sendMessage($chatId, $this->buildMenuText(), [
            'reply_markup' => [
                'inline_keyboard' => $this->buildMenuKeyboard($session),
            ],
            'parse_mode' => 'HTML',
        ]);

        $session->update([
            'current_flow' => 'main_menu',
            'current_step' => 'menu_shown',
        ]);
    }

    private function buildMenuText(): string
    {
        return "🤖 <b>BotPress Publisher</b>\n\nچه کاری می‌خواهی انجام دهی؟";
    }

    private function buildMenuKeyboard(ConversationSession $session): array
    {
        return [
            [['text' => '📝 محتوای جدید', 'callback_data' => 'action:new_content']],
            [['text' => '📋 پیش‌نویس‌ها', 'callback_data' => 'action:drafts']],
            [['text' => '📅 زمان‌بندی‌ها', 'callback_data' => 'action:scheduled']],
        ];
    }
}
