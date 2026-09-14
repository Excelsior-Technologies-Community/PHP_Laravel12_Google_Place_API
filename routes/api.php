<?php

use App\Http\Controllers\PlaceController;
use Illuminate\Support\Facades\Route;

// Existing Google Places APIs

Route::get('/places/search', [PlaceController::class, 'searchPlace']);

Route::get('/places/details/{placeId}', [PlaceController::class, 'placeDetails']);

Route::get('/places/nearby', [PlaceController::class, 'nearbyPlaces']);


// Search History APIs

Route::get('/places/history', [PlaceController::class, 'searchHistory']);

Route::delete('/places/history', [PlaceController::class, 'clearSearchHistory']);


// Favorite Places APIs

Route::post('/places/favorites', [PlaceController::class, 'addFavorite']);

Route::get('/places/favorites', [PlaceController::class, 'favorites']);

Route::delete('/places/favorites/{placeId}', [PlaceController::class, 'removeFavorite']);

Route::delete('/places/favorites', [PlaceController::class, 'clearFavorites']);