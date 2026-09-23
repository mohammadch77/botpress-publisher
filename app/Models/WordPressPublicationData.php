<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WordPressPublicationData extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'publication_id',
        'tenant_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'post_status',
        'post_type',
        'wp_category_ids',
        'wp_tag_ids',
        'wp_author_id',
        'wp_template_id',
        'featured_image_asset_id',
        'seo_title',
        'seo_description',
        'focus_keyword',
        'canonical_url',
        'robots',
        'elementor_data',
        'external_post_id',
        'external_url',
    ];

    protected $casts = [
        'post_status' => PostStatus::class,
        'wp_category_ids' => 'array',
        'wp_tag_ids' => 'array',
    ];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public function featuredImageAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'featured_image_asset_id');
    }
}
