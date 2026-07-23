<?php

use App\Http\Controllers\ApiStudentController;
use App\Http\Middleware\ApiTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware([ApiTokenMiddleware::class])->group(function () {
    Route::get('/students/scan-test-auth', function () {
        return response()->json([
            'success' => true,
            'message' => 'API Authentication Successful!',
        ]);
    });

    Route::post('/students/scan-onboard', [ApiStudentController::class, 'scanOnboard']);
});
