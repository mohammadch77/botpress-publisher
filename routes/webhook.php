<?php

use App\Http\Controllers\Bot\BaleWebhookController;
use App\Http\Controllers\Bot\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('telegram/{botUuid}', [TelegramWebhookController::class, 'handle']);
Route::post('bale/{botUuid}', [BaleWebhookController::class, 'handle']);
