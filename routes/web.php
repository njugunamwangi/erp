<?php

use App\Http\Controllers\DownloadInvoiceController;
use App\Http\Controllers\DownloadQuoteController;
use App\Http\Controllers\MPesaSTKPushController;
use App\Http\Controllers\ViewInvoiceController;
use App\Http\Controllers\ViewQuoteController;
use App\Livewire\AcceptInvitation;
use App\Livewire\Chats;
use App\Livewire\RequestFeedback;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    //Quotes
    Route::get('quote/{record}/pdf', ViewQuoteController::class)
        ->name('quote.view');

    Route::get('quote/download/{record}/pdf', DownloadQuoteController::class)
        ->name('quote.download');

    // Invoices
    Route::get('invoice/{record}/pdf', ViewInvoiceController::class)
        ->name('invoice.view');

    Route::get('invoice/download/{record}/pdf', DownloadInvoiceController::class)
        ->name('invoice.download');

    Route::get('chats', Chats::class)->name('chats');

    Route::post('stk-push', [MPesaSTKPushController::class, 'STKPush'])->name('mpesa.stk-push');
});

Route::middleware('signed')
    ->get('invitation/{invitation}/accept', AcceptInvitation::class)
    ->name('invitation.accept');

Route::middleware('signed')
    ->get('task/{task}/feedback', RequestFeedback::class)
    ->name('task.feedback');
