<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WpContentProfile extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'wordpress_site_id',
        'tenant_id',
        'name',
        'post_type',
        'category_id',
        'template_id',
        'required_fields',
        'recommended_structure',
        'seo_config',
        'avg_word_count',
        'sample_count',
        'is_default',
    ];

    protected $casts = [
        'required_fields' => 'array',
        'recommended_structure' => 'array',
        'seo_config' => 'array',
        'is_default' => 'boolean',
    ];

    public function wordpressSite(): BelongsTo
    {
        return $this->belongsTo(WordPressSite::class, 'wordpress_site_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WpCategory::class, 'category_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WpElementorTemplate::class, 'template_id');
    }
}
