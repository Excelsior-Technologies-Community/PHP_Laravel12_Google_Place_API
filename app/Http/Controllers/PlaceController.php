<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Avcodewizard\GooglePlaceApi\GooglePlacesApi;


class PlaceController extends Controller
{
public function searchPlace(Request $request)
{
$query = $request->input('query');


$googlePlaces = new GooglePlacesApi();
$results = $googlePlaces->searchPlace($query);


return response()->json($results);
}


public function placeDetails($placeId)
{
$googlePlaces = new GooglePlacesApi();
$results = $googlePlaces->getPlaceDetails($placeId);


return response()->json($results);
}


public function nearbyPlaces(Request $request)
{
$latitude = $request->latitude;
$longitude = $request->longitude;
$radius = $request->radius;
$type = $request->type; // optional


$googlePlaces = new GooglePlacesApi();
$results = $googlePlaces->findNearbyPlaces(
$latitude,
$longitude,
$radius,
$type
);


return response()->json($results);
}
}