<?php

namespace App\Models;

use App\Enums\DestinationPermission;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DestinationUser extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'destination_id',
        'user_id',
        'tenant_id',
        'permission',
        'granted_by',
        'granted_at',
    ];

    protected $casts = [
        'permission' => DestinationPermission::class,
        'granted_at' => 'datetime',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
