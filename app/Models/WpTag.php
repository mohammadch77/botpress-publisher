<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WpTag extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'wordpress_site_id',
        'tenant_id',
        'external_id',
        'name',
        'slug',
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
}
