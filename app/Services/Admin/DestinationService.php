<?php

namespace App\Services\Admin;

use App\Models\BaleDestination;
use App\Models\Destination;
use App\Models\DestinationUser;
use App\Models\TelegramDestination;
use App\Models\WordPressSite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DestinationService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Destination::query()->latest();

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function find(string $uuid): Destination
    {
        return Destination::query()
            ->with(['wordpressSite', 'telegramDestination', 'baleDestination'])
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function create(array $data): Destination
    {
        return DB::transaction(function () use ($data) {
            $destination = Destination::query()->create([
                'type' => $data['type'],
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'status' => 'pending',
            ]);

            match ($data['type']) {
                'wordpress_site' => WordPressSite::query()->create([
                    'destination_id' => $destination->id,
                    'tenant_id' => $destination->tenant_id,
                    'url' => $data['url'],
                    'api_key_encrypted' => Crypt::encryptString($data['api_key']),
                    'api_key_hash' => hash('sha256', $data['api_key']),
                ]),
                'telegram_channel', 'telegram_group' => TelegramDestination::query()->create([
                    'destination_id' => $destination->id,
                    'tenant_id' => $destination->tenant_id,
                    'bot_id' => $data['bot_id'],
                    'external_chat_id' => $data['external_chat_id'],
                ]),
                'bale_channel', 'bale_group' => BaleDestination::query()->create([
                    'destination_id' => $destination->id,
                    'tenant_id' => $destination->tenant_id,
                    'bot_id' => $data['bot_id'],
                    'external_chat_id' => $data['external_chat_id'],
                ]),
                default => null,
            };

            return $destination->fresh(['wordpressSite', 'telegramDestination', 'baleDestination']);
        });
    }

    public function update(Destination $destination, array $data): Destination
    {
        $destination->update(collect($data)->only(['name', 'status'])->toArray());

        if (! empty($data['url']) && $destination->wordpressSite) {
            $destination->wordpressSite->update(['url' => $data['url']]);
        }

        if (! empty($data['api_key']) && $destination->wordpressSite) {
            $destination->wordpressSite->update([
                'api_key_encrypted' => Crypt::encryptString($data['api_key']),
                'api_key_hash' => hash('sha256', $data['api_key']),
            ]);
        }

        return $destination->fresh(['wordpressSite', 'telegramDestination', 'baleDestination']);
    }

    public function delete(Destination $destination): void
    {
        $destination->delete();
    }

    public function testConnection(Destination $destination): array
    {
        // Real WordPress / Telegram / Bale connectivity checks are out of scope for Phase 1.
        return [
            'connected' => false,
            'message' => 'Not implemented yet',
        ];
    }

    public function addUser(Destination $destination, int $userId, string $permission = 'publish', ?int $grantedBy = null): DestinationUser
    {
        return DestinationUser::query()->updateOrCreate(
            ['destination_id' => $destination->id, 'user_id' => $userId],
            [
                'tenant_id' => $destination->tenant_id,
                'permission' => $permission,
                'granted_by' => $grantedBy,
                'granted_at' => now(),
            ]
        );
    }

    public function removeUser(Destination $destination, int $userId): void
    {
        DestinationUser::query()
            ->where('destination_id', $destination->id)
            ->where('user_id', $userId)
            ->delete();
    }
}
