<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/password/reset/{token}', function (string $token) {
    return redirect()->away(config('app.frontend_url').'/password/reset?token='.$token);
})->name('password.reset');
