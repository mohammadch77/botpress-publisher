<?php

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tenant
 */
class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'plan' => $this->plan,
            'status' => $this->status,
            'settings' => $this->settings,
            'trial_ends_at' => $this->trial_ends_at,
            'stats' => $this->when(
                $this->relationLoaded('users') || isset($this->stats_users),
                fn () => [
                    'users' => $this->stats_users ?? $this->users_count ?? null,
                    'bots' => $this->stats_bots ?? $this->bots_count ?? null,
                    'publications' => $this->stats_publications ?? $this->publications_count ?? null,
                ]
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
