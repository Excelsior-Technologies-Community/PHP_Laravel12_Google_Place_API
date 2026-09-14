<?php

namespace App\Http\Controllers;

use App\Models\FavoritePlace;
use App\Models\PlaceSearchHistory;
use Avcodewizard\GooglePlaceApi\GooglePlacesApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PlaceController extends Controller
{
    /**
     * Search places using Google Places API.
     *
     * Includes:
     * - Search history
     * - API response caching
     */
    public function searchPlace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = trim($request->input('query'));

        // Save search history
        PlaceSearchHistory::create([
            'query' => $query,
            'ip_address' => $request->ip(),
        ]);

        // Generate unique cache key
        $cacheKey = 'google_places_search_' . md5(strtolower($query));

        // Get data from cache or call Google Places API
        $wasCached = Cache::has($cacheKey);

        $results = Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            function () use ($query) {
                $googlePlaces = new GooglePlacesApi();

                return $googlePlaces->searchPlace($query);
            }
        );

        return response()->json([
            'success' => true,
            'cached' => $wasCached,
            'query' => $query,
            'data' => $results,
        ]);
    }

    /**
     * Get place details using Google Place ID.
     *
     * Includes response caching.
     */
    public function placeDetails($placeId)
    {
        if (empty($placeId)) {
            return response()->json([
                'success' => false,
                'message' => 'Place ID is required.',
            ], 422);
        }

        $cacheKey = 'google_place_details_' . md5($placeId);

        $results = Cache::remember(
            $cacheKey,
            now()->addMinutes(60),
            function () use ($placeId) {
                $googlePlaces = new GooglePlacesApi();

                return $googlePlaces->getPlaceDetails($placeId);
            }
        );

        return response()->json([
            'success' => true,
            'place_id' => $placeId,
            'data' => $results,
        ]);
    }

    /**
     * Find nearby places.
     *
     * Includes response caching.
     */
    public function nearbyPlaces(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:1', 'max:50000'],
            'type' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $radius = $request->input('radius');
        $type = $request->input('type');

        $cacheKey = 'google_places_nearby_' . md5(
            $latitude . '|' .
                $longitude . '|' .
                $radius . '|' .
                ($type ?? '')
        );

        $results = Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            function () use ($latitude, $longitude, $radius, $type) {
                $googlePlaces = new GooglePlacesApi();

                return $googlePlaces->findNearbyPlaces(
                    $latitude,
                    $longitude,
                    $radius,
                    $type
                );
            }
        );

        return response()->json([
            'success' => true,
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius' => $radius,
                'type' => $type,
            ],
            'data' => $results,
        ]);
    }

    /**
     * Get current visitor's search history.
     */
    public function searchHistory(Request $request)
    {
        $history = PlaceSearchHistory::where(
            'ip_address',
            $request->ip()
        )
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Search history retrieved successfully.',
            'data' => $history,
        ]);
    }

    /**
     * Clear current visitor's search history.
     */
    public function clearSearchHistory(Request $request)
    {
        PlaceSearchHistory::where(
            'ip_address',
            $request->ip()
        )->delete();

        return response()->json([
            'success' => true,
            'message' => 'Search history cleared successfully.',
        ]);
    }

    /**
     * Add a place to favorites.
     */
    public function addFavorite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'place_id' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $placeId = trim($request->input('place_id'));

        $existingFavorite = FavoritePlace::where('place_id', $placeId)
            ->where('ip_address', $request->ip())
            ->first();

        if ($existingFavorite) {
            return response()->json([
                'success' => false,
                'message' => 'This place is already in your favorites.',
                'data' => $existingFavorite,
            ], 409);
        }

        $favorite = FavoritePlace::create([
            'place_id' => $placeId,
            'name' => $request->input('name'),
            'address' => $request->input('address'),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Place added to favorites successfully.',
            'data' => $favorite,
        ], 201);
    }

    /**
     * Get current visitor's favorite places.
     */
    public function favorites(Request $request)
    {
        $favorites = FavoritePlace::where(
            'ip_address',
            $request->ip()
        )
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Favorite places retrieved successfully.',
            'data' => $favorites,
        ]);
    }

    /**
     * Remove a place from favorites.
     */
    public function removeFavorite(Request $request, $placeId)
    {
        $favorite = FavoritePlace::where('place_id', $placeId)
            ->where('ip_address', $request->ip())
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Favorite place not found.',
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Place removed from favorites successfully.',
        ]);
    }

    /**
     * Clear all current visitor's favorites.
     */
    public function clearFavorites(Request $request)
    {
        FavoritePlace::where(
            'ip_address',
            $request->ip()
        )->delete();

        return response()->json([
            'success' => true,
            'message' => 'All favorite places cleared successfully.',
        ]);
    }
}
