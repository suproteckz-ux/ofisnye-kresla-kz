<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KaspiPhotoImportLog extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'product_url',
        'kaspi_button_found',
        'kaspi_widget_opened',
        'resolved_kaspi_url',
        'kaspi_page_loaded',
        'photos_found',
        'photos_downloaded',
        'photos_saved',
        'duplicates_skipped',
        'main_image_changed',
        'status',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'kaspi_button_found' => 'boolean',
        'kaspi_widget_opened' => 'boolean',
        'kaspi_page_loaded' => 'boolean',
        'main_image_changed' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
