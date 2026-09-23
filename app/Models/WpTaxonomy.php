<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WpTaxonomy extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'wordpress_site_id',
        'tenant_id',
        'slug',
        'label',
        'post_types',
        'is_hierarchical',
    ];

    protected $casts = [
        'post_types' => 'array',
        'is_hierarchical' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function wordpressSite(): BelongsTo
    {
        return $this->belongsTo(WordPressSite::class, 'wordpress_site_id');
    }
}
