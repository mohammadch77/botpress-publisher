<?php

namespace App\Models;

use App\Enums\DiscoveryStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WordPressSite extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'destination_id',
        'tenant_id',
        'url',
        'api_key_encrypted',
        'api_key_hash',
        'plugin_version',
        'wp_version',
        'has_elementor',
        'has_rank_math',
        'has_yoast',
        'seo_plugin',
        'site_profile',
        'content_profile',
        'discovery_completed_at',
        'discovery_status',
    ];

    protected $hidden = [
        'api_key_encrypted',
    ];

    protected $casts = [
        'has_elementor' => 'boolean',
        'has_rank_math' => 'boolean',
        'has_yoast' => 'boolean',
        'site_profile' => 'array',
        'content_profile' => 'array',
        'discovery_completed_at' => 'datetime',
        'discovery_status' => DiscoveryStatus::class,
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function postTypes(): HasMany
    {
        return $this->hasMany(WpPostType::class);
    }

    public function taxonomies(): HasMany
    {
        return $this->hasMany(WpTaxonomy::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(WpCategory::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(WpTag::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(WpAuthor::class);
    }

    public function elementorTemplates(): HasMany
    {
        return $this->hasMany(WpElementorTemplate::class);
    }

    public function contentProfiles(): HasMany
    {
        return $this->hasMany(WpContentProfile::class);
    }
}
