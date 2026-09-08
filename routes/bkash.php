<?php

use Illuminate\Support\Facades\Route;
use Tiash\LaravelBkash\Http\Controllers\CallbackController;
use Tiash\LaravelBkash\Http\Controllers\WebhookController;

Route::group(['middleware' => ['web']], function () {
    Route::get('/bkash/callback', [CallbackController::class, 'handle'])
        ->name('bkash.callback');

    Route::post('/bkash/webhook', [WebhookController::class, 'handle'])
        ->name('bkash.webhook');
});