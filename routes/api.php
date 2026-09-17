<?php

use App\Http\Controllers\PlaceController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Existing Google Places APIs
|--------------------------------------------------------------------------
*/

Route::get(
    '/places/search',
    [PlaceController::class, 'searchPlace']
);

Route::get(
    '/places/details/{placeId}',
    [PlaceController::class, 'placeDetails']
);

Route::get(
    '/places/nearby',
    [PlaceController::class, 'nearbyPlaces']
);

Route::get(
    '/places/autocomplete',
    [PlaceController::class, 'autocomplete']
);

Route::get(
    '/places/distance',
    [PlaceController::class, 'calculateDistance']
);


/*
|--------------------------------------------------------------------------
| NEW 1 - Advanced Search
|--------------------------------------------------------------------------
*/

Route::get(
    '/places/advanced-search',
    [PlaceController::class, 'advancedSearch']
);


/*
|--------------------------------------------------------------------------
| Search History
|--------------------------------------------------------------------------
*/

Route::get(
    '/places/history',
    [PlaceController::class, 'searchHistory']
);

Route::get(
    '/places/history/filter',
    [PlaceController::class, 'filterSearchHistory']
);

Route::delete(
    '/places/history/{id}',
    [PlaceController::class, 'deleteSearchHistory']
);

Route::delete(
    '/places/history',
    [PlaceController::class, 'clearSearchHistory']
);


/*
|--------------------------------------------------------------------------
| NEW 7 - Popular Searches
|--------------------------------------------------------------------------
*/

Route::get(
    '/places/popular-searches',
    [PlaceController::class, 'popularSearches']
);


/*
|--------------------------------------------------------------------------
| NEW 6 - Statistics
|--------------------------------------------------------------------------
*/

Route::get(
    '/places/statistics',
    [PlaceController::class, 'statistics']
);


/*
|--------------------------------------------------------------------------
| NEW 8A - Export Search History
|--------------------------------------------------------------------------
*/

Route::get(
    '/places/history/export',
    [PlaceController::class, 'exportSearchHistory']
);


/*
|--------------------------------------------------------------------------
| Favorite Places
|--------------------------------------------------------------------------
*/

Route::post(
    '/places/favorites',
    [PlaceController::class, 'addFavorite']
);

Route::get(
    '/places/favorites',
    [PlaceController::class, 'favorites']
);

Route::delete(
    '/places/favorites/{placeId}',
    [PlaceController::class, 'removeFavorite']
);

Route::delete(
    '/places/favorites',
    [PlaceController::class, 'clearFavorites']
);


/*
|--------------------------------------------------------------------------
| NEW 4 - Favorite Toggle
|--------------------------------------------------------------------------
*/

Route::post(
    '/places/favorites/toggle',
    [PlaceController::class, 'toggleFavorite']
);


/*
|--------------------------------------------------------------------------
| NEW 5 - Favorite Status
|--------------------------------------------------------------------------
*/

Route::get(
    '/places/favorites/status/{placeId}',
    [PlaceController::class, 'favoriteStatus']
);


/*
|--------------------------------------------------------------------------
| NEW 8B - Export Favorites
|--------------------------------------------------------------------------
*/

Route::get(
    '/places/favorites/export',
    [PlaceController::class, 'exportFavorites']
);