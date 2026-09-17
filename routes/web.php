<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('places-explorer');
});

Route::get('/places', function () {
    return view('places-explorer');
});
