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
     * ============================================================
     * 1. SEARCH PLACES
     * ============================================================
     *
     * Existing search functionality.
     *
     * Includes:
     * - Validation
     * - Search history
     * - API caching
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

        // Cache key
        $cacheKey = 'google_places_search_' . md5(
            strtolower($query)
        );

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
     * ============================================================
     * 2. PLACE DETAILS
     * ============================================================
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
     * ============================================================
     * 3. NEARBY PLACES
     * ============================================================
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

        $wasCached = Cache::has($cacheKey);

        $results = Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            function () use (
                $latitude,
                $longitude,
                $radius,
                $type
            ) {
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
            'cached' => $wasCached,
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
     * ============================================================
     * NEW FUNCTIONALITY 1
     * ADVANCED SEARCH
     * ============================================================
     *
     * Parameters:
     * query
     * min_rating
     * sort
     *
     * Example:
     * /api/places/advanced-search?query=restaurant
     * &min_rating=4
     * &sort=rating_desc
     */
    public function advancedSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => ['required', 'string', 'min:2', 'max:255'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sort' => [
                'nullable',
                'in:rating_desc,rating_asc,name_asc,name_desc'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = trim($request->input('query'));
        $minimumRating = $request->input('min_rating');
        $sort = $request->input('sort', 'rating_desc');

        $cacheKey = 'google_places_advanced_' . md5(
            strtolower($query) .
            '|' .
            ($minimumRating ?? '') .
            '|' .
            $sort
        );

        $results = Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            function () use (
                $query,
                $minimumRating,
                $sort
            ) {
                $googlePlaces = new GooglePlacesApi();

                $data = $googlePlaces->searchPlace($query);

                /*
                 * The Google package can return different structures
                 * depending on the response.
                 *
                 * We only apply local filtering when the result is
                 * an array of place records.
                 */

                if (!is_array($data)) {
                    return $data;
                }

                $places = $data;

                if (
                    isset($data['results']) &&
                    is_array($data['results'])
                ) {
                    $places = $data['results'];
                }

                if (
                    $minimumRating !== null &&
                    is_array($places)
                ) {
                    $places = array_filter(
                        $places,
                        function ($place) use ($minimumRating) {
                            $rating = $place['rating']
                                ?? null;

                            return $rating !== null &&
                                (float) $rating >=
                                (float) $minimumRating;
                        }
                    );
                }

                if (is_array($places)) {
                    usort(
                        $places,
                        function ($a, $b) use ($sort) {
                            $nameA = strtolower(
                                $a['name'] ?? ''
                            );

                            $nameB = strtolower(
                                $b['name'] ?? ''
                            );

                            $ratingA = (float) (
                                $a['rating'] ?? 0
                            );

                            $ratingB = (float) (
                                $b['rating'] ?? 0
                            );

                            return match ($sort) {
                                'rating_desc' =>
                                    $ratingB <=> $ratingA,

                                'rating_asc' =>
                                    $ratingA <=> $ratingB,

                                'name_asc' =>
                                    $nameA <=> $nameB,

                                'name_desc' =>
                                    $nameB <=> $nameA,

                                default =>
                                    $ratingB <=> $ratingA,
                            };
                        }
                    );
                }

                if (
                    isset($data['results']) &&
                    is_array($data['results'])
                ) {
                    $data['results'] = array_values($places);

                    return $data;
                }

                return array_values($places);
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Advanced place search completed.',
            'filters' => [
                'query' => $query,
                'min_rating' => $minimumRating,
                'sort' => $sort,
            ],
            'data' => $results,
        ]);
    }


    /**
     * ============================================================
     * SEARCH HISTORY
     * ============================================================
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
            'message' =>
                'Search history retrieved successfully.',
            'data' => $history,
        ]);
    }


    /**
     * ============================================================
     * NEW FUNCTIONALITY 2
     * SEARCH HISTORY FILTER
     * ============================================================
     *
     * Parameters:
     * keyword
     * from_date
     * to_date
     *
     * Example:
     *
     * /api/places/history/filter?keyword=restaurant
     */
    public function filterSearchHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'keyword' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = PlaceSearchHistory::where(
            'ip_address',
            $request->ip()
        );

        if ($request->filled('keyword')) {
            $keyword = trim($request->input('keyword'));

            $query->where(
                'query',
                'like',
                '%' . $keyword . '%'
            );
        }

        if ($request->filled('from_date')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->input('from_date')
            );
        }

        if ($request->filled('to_date')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->input('to_date')
            );
        }

        $history = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' =>
                'Filtered search history retrieved successfully.',
            'filters' => [
                'keyword' => $request->input('keyword'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
            ],
            'data' => $history,
        ]);
    }


    /**
     * ============================================================
     * NEW FUNCTIONALITY 3
     * DELETE ONE SEARCH HISTORY RECORD
     * ============================================================
     */
    public function deleteSearchHistory(
        Request $request,
        $id
    ) {
        $history = PlaceSearchHistory::where(
            'ip_address',
            $request->ip()
        )
            ->where('id', $id)
            ->first();

        if (!$history) {
            return response()->json([
                'success' => false,
                'message' => 'Search history record not found.',
            ], 404);
        }

        $history->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Search history record deleted successfully.',
        ]);
    }


    /**
     * ============================================================
     * CLEAR SEARCH HISTORY
     * ============================================================
     */
    public function clearSearchHistory(Request $request)
    {
        $deleted = PlaceSearchHistory::where(
            'ip_address',
            $request->ip()
        )->delete();

        return response()->json([
            'success' => true,
            'message' => 'Search history cleared successfully.',
            'deleted_records' => $deleted,
        ]);
    }


    /**
     * ============================================================
     * ADD FAVORITE
     * ============================================================
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

        $placeId = trim(
            $request->input('place_id')
        );

        $existingFavorite = FavoritePlace::where(
            'place_id',
            $placeId
        )
            ->where(
                'ip_address',
                $request->ip()
            )
            ->first();

        if ($existingFavorite) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This place is already in your favorites.',
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
            'message' =>
                'Place added to favorites successfully.',
            'data' => $favorite,
        ], 201);
    }


    /**
     * ============================================================
     * GET FAVORITES
     * ============================================================
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
            'message' =>
                'Favorite places retrieved successfully.',
            'data' => $favorites,
        ]);
    }


    /**
     * ============================================================
     * NEW FUNCTIONALITY 4
     * FAVORITE TOGGLE
     * ============================================================
     *
     * If favorite exists -> remove it.
     * If favorite does not exist -> add it.
     */
    public function toggleFavorite(Request $request)
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

        $placeId = trim(
            $request->input('place_id')
        );

        $favorite = FavoritePlace::where(
            'place_id',
            $placeId
        )
            ->where(
                'ip_address',
                $request->ip()
            )
            ->first();

        if ($favorite) {
            $favorite->delete();

            return response()->json([
                'success' => true,
                'action' => 'removed',
                'message' =>
                    'Place removed from favorites.',
            ]);
        }

        $favorite = FavoritePlace::create([
            'place_id' => $placeId,
            'name' => $request->input('name'),
            'address' => $request->input('address'),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'action' => 'added',
            'message' =>
                'Place added to favorites.',
            'data' => $favorite,
        ], 201);
    }


    /**
     * ============================================================
     * NEW FUNCTIONALITY 5
     * CHECK FAVORITE STATUS
     * ============================================================
     */
    public function favoriteStatus(
        Request $request,
        $placeId
    ) {
        if (empty($placeId)) {
            return response()->json([
                'success' => false,
                'message' => 'Place ID is required.',
            ], 422);
        }

        $favorite = FavoritePlace::where(
            'place_id',
            $placeId
        )
            ->where(
                'ip_address',
                $request->ip()
            )
            ->first();

        return response()->json([
            'success' => true,
            'place_id' => $placeId,
            'is_favorite' => $favorite !== null,
            'data' => $favorite,
        ]);
    }


    /**
     * ============================================================
     * REMOVE FAVORITE
     * ============================================================
     */
    public function removeFavorite(
        Request $request,
        $placeId
    ) {
        $favorite = FavoritePlace::where(
            'place_id',
            $placeId
        )
            ->where(
                'ip_address',
                $request->ip()
            )
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
            'message' =>
                'Place removed from favorites successfully.',
        ]);
    }


    /**
     * ============================================================
     * CLEAR ALL FAVORITES
     * ============================================================
     */
    public function clearFavorites(Request $request)
    {
        $deleted = FavoritePlace::where(
            'ip_address',
            $request->ip()
        )->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'All favorite places cleared successfully.',
            'deleted_records' => $deleted,
        ]);
    }


    /**
     * ============================================================
     * NEW FUNCTIONALITY 6
     * PLACES STATISTICS
     * ============================================================
     */
    public function statistics(Request $request)
    {
        $ip = $request->ip();

        $totalSearches = PlaceSearchHistory::where(
            'ip_address',
            $ip
        )->count();

        $todaySearches = PlaceSearchHistory::where(
            'ip_address',
            $ip
        )
            ->whereDate(
                'created_at',
                today()
            )
            ->count();

        $totalFavorites = FavoritePlace::where(
            'ip_address',
            $ip
        )->count();

        $todayFavorites = FavoritePlace::where(
            'ip_address',
            $ip
        )
            ->whereDate(
                'created_at',
                today()
            )
            ->count();

        $uniqueSearches = PlaceSearchHistory::where(
            'ip_address',
            $ip
        )
            ->distinct('query')
            ->count('query');

        return response()->json([
            'success' => true,
            'message' =>
                'Place statistics retrieved successfully.',
            'data' => [
                'total_searches' => $totalSearches,
                'today_searches' => $todaySearches,
                'total_favorites' => $totalFavorites,
                'today_favorites' => $todayFavorites,
                'unique_searches' => $uniqueSearches,
            ],
        ]);
    }


    /**
     * ============================================================
     * NEW FUNCTIONALITY 7
     * MOST SEARCHED QUERIES
     * ============================================================
     */
    public function popularSearches(Request $request)
    {
        $limit = (int) $request->input('limit', 10);

        if ($limit < 1) {
            $limit = 10;
        }

        if ($limit > 50) {
            $limit = 50;
        }

        $popular = PlaceSearchHistory::where(
            'ip_address',
            $request->ip()
        )
            ->selectRaw(
                'query, COUNT(*) as search_count'
            )
            ->groupBy('query')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' =>
                'Popular searches retrieved successfully.',
            'data' => $popular,
        ]);
    }


    /**
     * ============================================================
     * NEW FUNCTIONALITY 8A
     * EXPORT SEARCH HISTORY CSV
     * ============================================================
     */
    public function exportSearchHistory(Request $request)
    {
        $history = PlaceSearchHistory::where(
            'ip_address',
            $request->ip()
        )
            ->latest()
            ->get();

        $fileName =
            'place_search_history_' .
            now()->format('Y_m_d_H_i_s') .
            '.csv';

        $headers = [
            'Content-Type' =>
                'text/csv; charset=UTF-8',
            'Content-Disposition' =>
                'attachment; filename="' .
                $fileName .
                '"',
        ];

        $callback = function () use ($history) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'Query',
                'IP Address',
                'Created At',
            ]);

            foreach ($history as $item) {
                fputcsv($file, [
                    $item->id,
                    $item->query,
                    $item->ip_address,
                    $item->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream(
            $callback,
            200,
            $headers
        );
    }


    /**
     * ============================================================
     * EXPORT FAVORITES CSV
     * ============================================================
     */
    public function exportFavorites(Request $request)
    {
        $favorites = FavoritePlace::where(
            'ip_address',
            $request->ip()
        )
            ->latest()
            ->get();

        $fileName =
            'favorite_places_' .
            now()->format('Y_m_d_H_i_s') .
            '.csv';

        $headers = [
            'Content-Type' =>
                'text/csv; charset=UTF-8',
            'Content-Disposition' =>
                'attachment; filename="' .
                $fileName .
                '"',
        ];

        $callback = function () use ($favorites) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'Place ID',
                'Name',
                'Address',
                'IP Address',
                'Created At',
            ]);

            foreach ($favorites as $favorite) {
                fputcsv($file, [
                    $favorite->id,
                    $favorite->place_id,
                    $favorite->name,
                    $favorite->address,
                    $favorite->ip_address,
                    $favorite->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream(
            $callback,
            200,
            $headers
        );
    }
}