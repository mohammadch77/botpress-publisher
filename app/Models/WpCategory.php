<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WpCategory extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'wordpress_site_id',
        'tenant_id',
        'external_id',
        'name',
        'slug',
        'parent_id',
        'taxonomy',
        'post_count',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    public function wordpressSite(): BelongsTo
    {
        return $this->belongsTo(WordPressSite::class, 'wordpress_site_id');
    }

    public function contentProfiles(): HasMany
    {
        return $this->hasMany(WpContentProfile::class, 'category_id');
    }
}
