<?php

use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SupportTicketController::class, 'index'])->name('support.index');
Route::get('/request', [SupportTicketController::class, 'create'])->name('support.create');
Route::post('/submit', [SupportTicketController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('support.store');
Route::post('/api/chatbot', [ChatbotController::class, 'chat'])
    ->middleware('throttle:20,1')
    ->name('api.chatbot');
