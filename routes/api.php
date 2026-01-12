<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlaceController;

Route::get('/places/search', [PlaceController::class, 'searchPlace']);
Route::get('/places/details/{placeId}', [PlaceController::class, 'placeDetails']);
Route::get('/places/nearby', [PlaceController::class, 'nearbyPlaces']);
