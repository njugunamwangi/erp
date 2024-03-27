<?php

use App\Http\Controllers\DownloadPDFController;
use App\Http\Controllers\DownloadQuoteController;
use App\Http\Controllers\QuotePdfController;
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

    Route::get('quote/{record}/pdf', ViewQuoteController::class)
        ->name('quote.pdf.download');

    Route::get('download/{record}/pdf', DownloadQuoteController::class)
        ->name('pdf.download');
});

Route::middleware('signed')
    ->get('invitation/{invitation}/accept', AcceptInvitation::class)
    ->name('invitation.accept');

