<?php

use App\Http\Controllers\MPesaSTKPushController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('v1/confirm', [MPesaSTKPushController::class, 'STKConfirm'])->name('mpesa.confirm');
