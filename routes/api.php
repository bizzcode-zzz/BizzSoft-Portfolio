<?php

use App\Http\Controllers\Api\LicenseActivationController;
use App\Http\Controllers\Api\LicenseValidationController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/licenses/activate',
    LicenseActivationController::class
)->middleware('throttle:licenses')->name('licenses.activate');

Route::post(
    '/licenses/validate',
    LicenseValidationController::class
)->middleware('throttle:licenses')->name('licenses.validate');
