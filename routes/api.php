<?php

use App\Http\Controllers\SeoManagerController;
use App\Http\Middleware\SeoManagerAccess;
use Illuminate\Support\Facades\Route;

Route::prefix('seo-manager/v1')->middleware('throttle:60,1')->group(function () {
    Route::get('/status', [SeoManagerController::class, 'status'])->middleware(SeoManagerAccess::class.':catalog:read');
    Route::get('/catalog/{kind}', [SeoManagerController::class, 'catalog'])->middleware(SeoManagerAccess::class.':catalog:read');
    Route::middleware(SeoManagerAccess::class.':drafts:write')->group(function () {
        Route::post('/drafts', [SeoManagerController::class, 'create']);
        Route::put('/drafts/{id}', [SeoManagerController::class, 'update'])->whereNumber('id');
        Route::post('/proposals', [SeoManagerController::class, 'proposal']);
    });
});
