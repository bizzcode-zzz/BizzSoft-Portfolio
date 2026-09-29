<?php

use App\Http\Controllers\Api\LicenseActivationController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/licenses/activate',
    LicenseActivationController::class
)->name('licenses.activate');