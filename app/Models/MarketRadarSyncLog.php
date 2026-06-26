<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketRadarSyncLog extends Model
{
    protected $table = 'marketradar_sync_logs';

    protected $fillable = [
        'product_id',
        'sku',
        'offer_id',
        'vendor_code',
        'matched_by',
        'status',
        'old_price',
        'new_price',
        'old_quantity',
        'new_quantity',
        'old_available',
        'new_available',
        'photos_found',
        'photos_saved',
        'duplicates_skipped',
        'source_url',
        'error_message',
        'dry_run',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'old_available' => 'boolean',
        'new_available' => 'boolean',
        'dry_run' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
