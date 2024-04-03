<?php

use App\Http\Controllers\DownloadInvoiceController;
use App\Http\Controllers\DownloadQuoteController;
use App\Http\Controllers\ViewInvoiceController;
use App\Http\Controllers\ViewQuoteController;
use App\Livewire\AcceptInvitation;
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
});

Route::middleware('signed')
    ->get('invitation/{invitation}/accept', AcceptInvitation::class)
    ->name('invitation.accept');
