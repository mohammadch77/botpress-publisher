<?php

namespace App\Services\Admin;

use App\Enums\BotStatus;
use App\Models\Bot;
use App\Services\Bot\BotDriverFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;

class BotService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Bot::query()->latest();

        if (! empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function find(string $uuid): Bot
    {
        return Bot::query()->where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): Bot
    {
        $token = $data['token'];

        return Bot::query()->create([
            'platform' => $data['platform'],
            'name' => $data['name'],
            'token_encrypted' => $this->encryptToken($token),
            'token_hash' => $this->hashToken($token),
            'status' => 'pending',
            'metadata' => [
                'token_last_four' => substr($token, -4),
            ],
        ]);
    }

    public function update(Bot $bot, array $data): Bot
    {
        $attributes = collect($data)->only(['name', 'status'])->toArray();

        if (! empty($data['token'])) {
            $token = $data['token'];
            $attributes['token_encrypted'] = $this->encryptToken($token);
            $attributes['token_hash'] = $this->hashToken($token);
            $metadata = $bot->metadata ?? [];
            $metadata['token_last_four'] = substr($token, -4);
            $attributes['metadata'] = $metadata;
        }

        $bot->update($attributes);

        return $bot->refresh();
    }

    public function delete(Bot $bot): void
    {
        $bot->delete();
    }

    public function testConnection(Bot $bot): array
    {
        try {
            $driver = app(BotDriverFactory::class)->make($bot);
            $info = $driver->getMe();

            $bot->update([
                'status' => BotStatus::Active,
                'username' => $info->username,
                'metadata' => ['id' => $info->id, 'username' => $info->username],
                'last_error' => null,
            ]);

            return ['connected' => true, 'username' => $info->username];
        } catch (\Throwable $e) {
            $bot->update([
                'status' => BotStatus::Error,
                'last_error' => $e->getMessage(),
                'last_error_at' => now(),
            ]);

            return ['connected' => false, 'error' => 'Connection failed'];
        }
    }

    public function encryptToken(string $token): string
    {
        return Crypt::encryptString($token);
    }

    public function decryptToken(Bot $bot): string
    {
        return Crypt::decryptString($bot->token_encrypted);
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
