<?php

use App\Http\Controllers\Api\LicenseActivationController;
use App\Http\Controllers\Api\LicenseValidationController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/licenses/activate',
    LicenseActivationController::class
)->name('licenses.activate');

Route::post(
    '/licenses/validate',
    LicenseValidationController::class
)->name('licenses.validate');
