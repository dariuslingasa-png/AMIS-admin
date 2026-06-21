<?php

// Public enrollment routes stay outside this admin app.
// Admin portal routes are isolated in routes/admin.php.

use App\Http\Controllers\FacebookBotController;

Route::get('/messenger/webhook', [FacebookBotController::class, 'verify']);
Route::post('/messenger/webhook', [FacebookBotController::class, 'handle']);

