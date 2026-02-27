# PHP_Laravel12_Google_Place_API

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/Google%20Places%20API-Enabled-4285F4?style=for-the-badge&logo=google-maps&logoColor=white" />
  <img src="https://img.shields.io/badge/API-REST%20JSON-000000?style=for-the-badge&logo=json&logoColor=white" />
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" />
</p>


---

##  Overview

This project demonstrates how to integrate the **Google Places API** into a Laravel 12 application.
It enables developers to easily implement features such as address autocomplete, place details retrieval, and nearby location searches using latitude and longitude.

The integration is built using the Laravel package:

```
avcodewizard/google-place-api
```

---

##  Features

*  Place autocomplete search (cities, states, locations)
*  Fetch place details using `place_id`
*  Find nearby places using latitude & longitude
*  Simple REST API implementation
*  Secure API key handling via `.env`
*  Compatible with Laravel 9, 10, 11 & 12

---

##  Folder Structure

```
app/
 └── Http/
     └── Controllers/
         └── PlaceController.php

routes/
 ├── api.php
 └── web.php

.env
composer.json
```

---

##  Requirements

* PHP 8.1+
* Laravel 12
* Composer
* Google Cloud account with billing enabled
* Google Places API enabled

---

##  Step-by-Step Installation Guide

---

### Step 1: Create / Open Laravel Project

If you already have a Laravel project, skip this step.

```bash
composer create-project laravel/laravel google-places-demo
```

---

### Step 2: Environment Configuration

Update your `.env` file with database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

---

### Step 3: Install Google Places API Package

Install the package via Composer:

```bash
composer require avcodewizard/google-place-api
```

> The service provider is auto-registered by Laravel.

---

### Step 4: Get Google Places API Key

1. Open **Google Cloud Console**
2. Create or select a project
3. Enable the following APIs:

   * Places API
   * Maps JavaScript API (optional)
4. Enable **Billing**
5. Create an **API Key**
6. Restrict the key (recommended):

   * HTTP referrer (frontend)
   * IP address (backend)

---

### Step 5: Configure API Key in Laravel

Add the API key to your `.env` file:

```env
GOOGLE_PLACES_API_KEY=your_google_places_api_key
```

Clear and cache config:

```bash
php artisan config:clear

php artisan config:cache
```

---

### Step 6: Create Controller

Create a controller:

```bash
php artisan make:controller PlaceController
```

**`app/Http/Controllers/PlaceController.php`**

```php
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
        $latitude  = $request->latitude;
        $longitude = $request->longitude;
        $radius    = $request->radius;
        $type      = $request->type; // optional

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
```

---

### Step 7: Define API Routes

**`routes/api.php`**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlaceController;

Route::get('/places/search', [PlaceController::class, 'searchPlace']);
Route::get('/places/details/{placeId}', [PlaceController::class, 'placeDetails']);
Route::get('/places/nearby', [PlaceController::class, 'nearbyPlaces']);
```

---

### Step 8: Test Using Postman / Browser

####  Search Place

```
GET http://127.0.0.1:8000/api/places/search?query=Ahmedabad
```

####  Place Details

```
GET http://127.0.0.1:8000/api/places/details/PLACE_ID_HERE
```

####  Nearby Places

```
GET http://127.0.0.1:8000/api/places/nearby?latitude=23.0225&longitude=72.5714&radius=1500&type=restaurant
```

---

##  Common Use Cases

* Checkout address autocomplete
* Store locator
* Nearby restaurant / shop search
* Delivery radius validation
* City & state auto-fill

---

##  Security Notes

* Always restrict Google API keys
* Set billing budget alerts
* Cache API responses to reduce usage
* Never expose unrestricted keys publicly

---

##  License

This project is open-source and licensed under the **MIT License**.

---


