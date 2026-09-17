<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Google Places Live Explorer & Route Matrix | Laravel 12</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#4f46e5',
                            600: '#4338ca',
                            700: '#3730a3',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        #map {
            width: 100%;
            height: 100%;
            z-index: 10;
        }
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .pulse-marker {
            box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.7);
            animation: pulse-ring 1.8s infinite cubic-bezier(0.66, 0, 1, 1);
        }
        @keyframes pulse-ring {
            0% {
                box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.7);
            }
            70% {
                box-shadow: 0 0 0 16px rgba(79, 70, 229, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(79, 70, 229, 0);
            }
        }
        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
            padding: 2px;
        }
    </style>
</head>
<body class="h-full bg-slate-900 text-slate-100 flex flex-col overflow-hidden">

    <!-- Top Navbar -->
    <header class="h-16 bg-slate-900 border-b border-slate-800 px-4 sm:px-6 flex items-center justify-between z-30 shrink-0">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                <i class="fa-solid fa-location-dot text-lg"></i>
            </div>
            <div>
                <h1 class="text-base sm:text-lg font-bold text-white tracking-tight flex items-center gap-2">
                    Places Live Explorer
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Laravel 12</span>
                </h1>
                <p class="text-xs text-slate-400 hidden sm:block">Interactive Web Map, Typeahead Autocomplete & Route Distance Matrix</p>
            </div>
        </div>

        <div class="flex items-center space-x-2 sm:space-x-3">
            <button id="btn-my-location" onclick="getUserLocation()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs sm:text-sm font-medium rounded-lg border border-slate-700 hover:border-indigo-500 transition-all flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-crosshairs text-indigo-400"></i>
                <span class="hidden md:inline">My Location</span>
            </button>

            <button onclick="openDistanceModal()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs sm:text-sm font-medium rounded-lg border border-slate-700 hover:border-indigo-500 transition-all flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-route text-amber-400"></i>
                <span class="hidden md:inline">Distance Matrix</span>
            </button>

            <button onclick="openFavoritesDrawer()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs sm:text-sm font-medium rounded-lg border border-slate-700 hover:border-yellow-500 transition-all flex items-center gap-2 shadow-sm relative">
                <i class="fa-solid fa-star text-yellow-400"></i>
                <span class="hidden md:inline">Favorites</span>
                <span id="fav-count-badge" class="px-1.5 py-0.2 bg-yellow-500 text-slate-950 font-bold rounded-full text-[10px]">0</span>
            </button>

            <button onclick="openHistoryDrawer()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs sm:text-sm font-medium rounded-lg border border-slate-700 hover:border-blue-500 transition-all flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-clock-rotate-left text-cyan-400"></i>
                <span class="hidden md:inline">History</span>
            </button>
        </div>
    </header>

    <!-- Main Container (Left Sidebar + Right Map) -->
    <div class="flex-1 flex overflow-hidden relative">

        <!-- Left Search & Place Results Sidebar -->
        <aside class="w-full md:w-[420px] lg:w-[460px] bg-slate-900 border-r border-slate-800 flex flex-col z-20 shrink-0 shadow-2xl h-full">

            <!-- Search Box & Autocomplete -->
            <div class="p-4 border-b border-slate-800 bg-slate-900/90 backdrop-blur">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </div>
                    <input
                        type="text"
                        id="place-search-input"
                        placeholder="Search places, restaurants, hospitals, ATMs..."
                        autocomplete="off"
                        class="w-full pl-10 pr-10 py-2.5 bg-slate-800/90 text-slate-100 placeholder-slate-400 text-sm rounded-xl border border-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all shadow-inner"
                    >
                    <button id="clear-search-btn" onclick="clearSearch()" class="hidden absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-200">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                    <!-- Typeahead Autocomplete Dropdown List -->
                    <div id="autocomplete-dropdown" class="hidden absolute left-0 right-0 top-full mt-1.5 bg-slate-800/95 backdrop-blur-md rounded-xl border border-slate-700 shadow-2xl z-50 max-h-72 overflow-y-auto custom-scroll divide-y divide-slate-700/50">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- Quick Category Chips -->
                <div class="flex items-center gap-1.5 mt-3 overflow-x-auto custom-scroll pb-1">
                    <button onclick="filterCategory('restaurant')" class="category-chip px-2.5 py-1 bg-slate-800 hover:bg-indigo-600/30 text-slate-300 hover:text-indigo-200 rounded-lg text-xs font-medium border border-slate-700/80 transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>🍽️</span> Restaurant
                    </button>
                    <button onclick="filterCategory('cafe')" class="category-chip px-2.5 py-1 bg-slate-800 hover:bg-indigo-600/30 text-slate-300 hover:text-indigo-200 rounded-lg text-xs font-medium border border-slate-700/80 transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>☕</span> Cafe
                    </button>
                    <button onclick="filterCategory('hospital')" class="category-chip px-2.5 py-1 bg-slate-800 hover:bg-indigo-600/30 text-slate-300 hover:text-indigo-200 rounded-lg text-xs font-medium border border-slate-700/80 transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>🏥</span> Hospital
                    </button>
                    <button onclick="filterCategory('atm')" class="category-chip px-2.5 py-1 bg-slate-800 hover:bg-indigo-600/30 text-slate-300 hover:text-indigo-200 rounded-lg text-xs font-medium border border-slate-700/80 transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>🏧</span> ATM
                    </button>
                    <button onclick="filterCategory('lodging')" class="category-chip px-2.5 py-1 bg-slate-800 hover:bg-indigo-600/30 text-slate-300 hover:text-indigo-200 rounded-lg text-xs font-medium border border-slate-700/80 transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>🏨</span> Hotel
                    </button>
                    <button onclick="filterCategory('gas_station')" class="category-chip px-2.5 py-1 bg-slate-800 hover:bg-indigo-600/30 text-slate-300 hover:text-indigo-200 rounded-lg text-xs font-medium border border-slate-700/80 transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>⛽</span> Gas
                    </button>
                    <button onclick="filterCategory('supermarket')" class="category-chip px-2.5 py-1 bg-slate-800 hover:bg-indigo-600/30 text-slate-300 hover:text-indigo-200 rounded-lg text-xs font-medium border border-slate-700/80 transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>🛒</span> Market
                    </button>
                </div>

                <!-- Radius and Rating Filter -->
                <div class="mt-3 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                    <div class="flex items-center gap-2">
                        <span>Radius:</span>
                        <select id="radius-select" onchange="applyFilters()" class="bg-slate-800 text-slate-200 text-xs px-2 py-1 rounded border border-slate-700 focus:outline-none">
                            <option value="1000">1 km</option>
                            <option value="3000">3 km</option>
                            <option value="5000" selected>5 km</option>
                            <option value="10000">10 km</option>
                            <option value="25000">25 km</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <span>Min Rating:</span>
                        <select id="rating-select" onchange="applyFilters()" class="bg-slate-800 text-slate-200 text-xs px-2 py-1 rounded border border-slate-700 focus:outline-none">
                            <option value="0">All</option>
                            <option value="3">⭐ 3.0+</option>
                            <option value="4" selected>⭐ 4.0+</option>
                            <option value="4.5">⭐ 4.5+</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Results Status Header -->
            <div class="px-4 py-2.5 bg-slate-900/95 border-b border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                <span id="results-count-text">Showing <strong class="text-slate-200">0</strong> places</span>
                <span id="active-location-label" class="text-indigo-400 truncate max-w-[200px]">📍 Default Central</span>
            </div>

            <!-- Place Cards List Container -->
            <div id="places-list" class="flex-1 overflow-y-auto p-3 space-y-3 custom-scroll">
                <!-- Place cards rendered via JS -->
            </div>
        </aside>

        <!-- Right Map Container -->
        <main class="flex-1 relative h-full bg-slate-950">
            <div id="map"></div>

            <!-- Map Floating Action Controls -->
            <div class="absolute bottom-6 right-6 z-20 flex flex-col space-y-2">
                <button onclick="recenterMap()" title="Recenter to active pin" class="w-11 h-11 bg-slate-900/90 hover:bg-slate-800 text-white rounded-xl shadow-xl border border-slate-700 flex items-center justify-center transition-all">
                    <i class="fa-solid fa-location-crosshairs text-indigo-400 text-base"></i>
                </button>
                <button onclick="openDistanceModal()" title="Calculate Distance" class="w-11 h-11 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl shadow-xl shadow-indigo-600/30 flex items-center justify-center transition-all">
                    <i class="fa-solid fa-route text-base"></i>
                </button>
            </div>
        </main>
    </div>

    <!-- MODAL 1: DISTANCE & TRAVEL DURATION MATRIX CALCULATOR -->
    <div id="distance-modal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-800/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <i class="fa-solid fa-route text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Distance & Travel Duration Matrix</h3>
                        <p class="text-xs text-slate-400">Compute live travel times across Driving, Walking, Bicycling & Transit</p>
                    </div>
                </div>
                <button onclick="closeDistanceModal()" class="text-slate-400 hover:text-white text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 space-y-4 overflow-y-auto custom-scroll">
                <!-- Origin -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Origin (Starting Point / Coordinates)</label>
                    <div class="relative">
                        <input type="text" id="route-origin" placeholder="e.g. 23.0225,72.5714 or City Center" class="w-full pl-9 pr-24 py-2.5 bg-slate-800 text-slate-100 text-sm rounded-xl border border-slate-700 focus:border-indigo-500 focus:outline-none">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-emerald-400">
                            <i class="fa-solid fa-circle-dot text-xs"></i>
                        </div>
                        <button onclick="useCurrentAsOrigin()" class="absolute inset-y-1.5 right-1.5 px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs rounded-lg transition-all">
                            Use Current
                        </button>
                    </div>
                </div>

                <!-- Destination -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Destination (Target Place / Coordinates)</label>
                    <div class="relative">
                        <input type="text" id="route-destination" placeholder="e.g. 23.0338,72.5850 or Airport" class="w-full pl-9 pr-3 py-2.5 bg-slate-800 text-slate-100 text-sm rounded-xl border border-slate-700 focus:border-indigo-500 focus:outline-none">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-rose-400">
                            <i class="fa-solid fa-location-dot text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Mode Selector -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Primary Travel Mode</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button type="button" onclick="selectRouteMode('driving')" id="mode-btn-driving" class="route-mode-btn active py-2 px-2 bg-indigo-600 text-white rounded-xl text-xs font-medium border border-indigo-500 flex flex-col items-center gap-1 transition-all">
                            <i class="fa-solid fa-car text-sm"></i>
                            <span>Driving</span>
                        </button>
                        <button type="button" onclick="selectRouteMode('transit')" id="mode-btn-transit" class="route-mode-btn py-2 px-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-medium border border-slate-700 flex flex-col items-center gap-1 transition-all">
                            <i class="fa-solid fa-bus text-sm"></i>
                            <span>Transit</span>
                        </button>
                        <button type="button" onclick="selectRouteMode('bicycling')" id="mode-btn-bicycling" class="route-mode-btn py-2 px-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-medium border border-slate-700 flex flex-col items-center gap-1 transition-all">
                            <i class="fa-solid fa-bicycle text-sm"></i>
                            <span>Bicycling</span>
                        </button>
                        <button type="button" onclick="selectRouteMode('walking')" id="mode-btn-walking" class="route-mode-btn py-2 px-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-medium border border-slate-700 flex flex-col items-center gap-1 transition-all">
                            <i class="fa-solid fa-person-walking text-sm"></i>
                            <span>Walking</span>
                        </button>
                    </div>
                </div>

                <button onclick="calculateRouteDistance()" id="btn-calc-matrix" class="w-full py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-calculator"></i>
                    <span>Calculate Matrix</span>
                </button>

                <!-- Calculation Result Card -->
                <div id="matrix-result-container" class="hidden space-y-3 pt-3 border-t border-slate-800">
                    <div class="bg-indigo-950/40 border border-indigo-500/30 rounded-xl p-4 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-indigo-300 uppercase tracking-wider font-semibold">Total Distance</span>
                            <h4 id="res-distance-km" class="text-2xl font-black text-white">0 km</h4>
                            <span id="res-distance-mi" class="text-xs text-slate-400">0 miles</span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-indigo-300 uppercase tracking-wider font-semibold">Estimated ETA</span>
                            <h4 id="res-duration-text" class="text-2xl font-black text-amber-400">0 min</h4>
                            <span id="res-mode-badge" class="text-xs text-slate-400">Driving</span>
                        </div>
                    </div>

                    <!-- All Modes Breakdown Grid -->
                    <div class="text-xs font-semibold text-slate-300">Multi-Modal Comparison:</div>
                    <div id="all-modes-grid" class="grid grid-cols-2 gap-2 text-xs">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DRAWER 2: FAVORITES DRAWER -->
    <div id="favorites-drawer" class="fixed inset-y-0 right-0 w-full sm:w-96 bg-slate-900 border-l border-slate-800 z-50 transform translate-x-full transition-transform duration-300 shadow-2xl flex flex-col">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-800/40">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-star text-yellow-400"></i>
                <h3 class="font-bold text-white text-base">Favorite Places</h3>
            </div>
            <div class="flex items-center gap-2">
                <a href="/api/places/favorites/export" target="_blank" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs rounded-lg border border-slate-700 flex items-center gap-1 transition-all" title="Export CSV">
                    <i class="fa-solid fa-file-arrow-down text-xs"></i> Export
                </a>
                <button onclick="closeFavoritesDrawer()" class="text-slate-400 hover:text-white text-lg p-1">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div id="favorites-list" class="flex-1 overflow-y-auto p-4 space-y-2.5 custom-scroll">
            <!-- Populated by JS -->
        </div>
    </div>

    <!-- DRAWER 3: SEARCH HISTORY DRAWER -->
    <div id="history-drawer" class="fixed inset-y-0 right-0 w-full sm:w-96 bg-slate-900 border-l border-slate-800 z-50 transform translate-x-full transition-transform duration-300 shadow-2xl flex flex-col">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-800/40">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-cyan-400"></i>
                <h3 class="font-bold text-white text-base">Search History</h3>
            </div>
            <div class="flex items-center gap-2">
                <a href="/api/places/history/export" target="_blank" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs rounded-lg border border-slate-700 flex items-center gap-1 transition-all" title="Export CSV">
                    <i class="fa-solid fa-file-arrow-down text-xs"></i> Export
                </a>
                <button onclick="closeHistoryDrawer()" class="text-slate-400 hover:text-white text-lg p-1">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div id="history-list" class="flex-1 overflow-y-auto p-4 space-y-2.5 custom-scroll">
            <!-- Populated by JS -->
        </div>
    </div>

    <!-- SCRIPTS -->
    <script>
        // State
        let map;
        let markersGroup;
        let routeLineLayer;
        let userLocationMarker;
        let currentCenter = { lat: 23.0225, lng: 72.5714 }; // Default
        let activePlaces = [];
        let favoritePlaceIds = new Set();
        let selectedRouteMode = 'driving';
        let debounceTimer = null;

        // Custom Leaflet Icons
        function getCategoryIcon(type) {
            const icons = {
                restaurant: '🍽️',
                cafe: '☕',
                hospital: '🏥',
                atm: '🏧',
                lodging: '🏨',
                gas_station: '⛽',
                supermarket: '🛒',
                school: '🎓'
            };
            return icons[type] || '📍';
        }

        // Initialize Leaflet Map
        function initMap() {
            map = L.map('map', {
                zoomControl: true
            }).setView([currentCenter.lat, currentCenter.lng], 13);

            // OpenStreetMap CartoDB Dark Matter / Standard Tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            markersGroup = L.layerGroup().addTo(map);

            // Setup map click to inspect or pick coordinates
            map.on('click', function(e) {
                const lat = e.latlng.lat.toFixed(5);
                const lng = e.latlng.lng.toFixed(5);
                // Optionally fill destination if distance modal is open
                const destInput = document.getElementById('route-destination');
                if (destInput && document.getElementById('distance-modal').classList.contains('flex')) {
                    destInput.value = `${lat},${lng}`;
                }
            });

            // Initial load of nearby places
            fetchNearbyPlaces(currentCenter.lat, currentCenter.lng);
            loadFavorites();
        }

        // User Geolocation Trigger
        function getUserLocation() {
            const btn = document.getElementById('btn-my-location');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-indigo-400"></i> Locating...';

            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                btn.innerHTML = '<i class="fa-solid fa-crosshairs text-indigo-400"></i> My Location';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    currentCenter = { lat, lng };

                    document.getElementById('active-location-label').innerText = `📍 GPS (${lat.toFixed(4)}, ${lng.toFixed(4)})`;

                    if (userLocationMarker) {
                        map.removeLayer(userLocationMarker);
                    }

                    // Pulse blue marker for user location
                    const userIcon = L.divIcon({
                        className: 'custom-user-marker',
                        html: `<div class="w-5 h-5 bg-indigo-500 rounded-full border-2 border-white pulse-marker"></div>`,
                        iconSize: [20, 20],
                        iconAnchor: [10, 10]
                    });

                    userLocationMarker = L.marker([lat, lng], { icon: userIcon })
                        .addTo(map)
                        .bindPopup('<b>📍 Your Current Location</b>')
                        .openPopup();

                    map.flyTo([lat, lng], 14, { animate: true, duration: 1.2 });
                    fetchNearbyPlaces(lat, lng);
                    btn.innerHTML = '<i class="fa-solid fa-crosshairs text-indigo-400"></i> My Location';
                },
                (err) => {
                    console.warn('Geolocation denied/failed, falling back to central coordinates.');
                    btn.innerHTML = '<i class="fa-solid fa-crosshairs text-indigo-400"></i> My Location';
                    fetchNearbyPlaces(currentCenter.lat, currentCenter.lng);
                },
                { timeout: 8000, enableHighAccuracy: true }
            );
        }

        // Fetch Nearby Places
        async function fetchNearbyPlaces(lat, lng, type = '') {
            const radius = document.getElementById('radius-select').value;
            const placesContainer = document.getElementById('places-list');
            placesContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12 text-slate-400 space-y-2">
                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-indigo-400"></i>
                    <p class="text-xs">Fetching places nearby...</p>
                </div>
            `;

            try {
                let url = `/api/places/nearby?latitude=${lat}&longitude=${lng}&radius=${radius}`;
                if (type) url += `&type=${type}`;

                const res = await fetch(url);
                const data = await res.json();

                let results = [];
                if (data.data && data.data.results && Array.isArray(data.data.results)) {
                    results = data.data.results;
                } else if (data.data && Array.isArray(data.data)) {
                    results = data.data;
                }

                // If Google key not active or 0 results, generate contextual fallback pins around current lat/lng
                if (!results || results.length === 0) {
                    results = generateClientMockPlaces(lat, lng, type);
                }

                activePlaces = results;
                renderPlaces(activePlaces);
                renderMarkers(activePlaces);
            } catch (e) {
                console.error('Fetch places error:', e);
                activePlaces = generateClientMockPlaces(lat, lng, type);
                renderPlaces(activePlaces);
                renderMarkers(activePlaces);
            }
        }

        // Generate client-side places around center
        function generateClientMockPlaces(lat, lng, filterType = '') {
            const list = [
                { name: 'The Grand Gourmet Kitchen & Cafe', type: 'restaurant', rating: 4.8, address: 'Main Blvd, Central District', dLat: 0.008, dLng: 0.006, price_level: 2 },
                { name: 'Blue Tokai Specialty Roastery', type: 'cafe', rating: 4.7, address: 'Lakefront Promenade, Block C', dLat: -0.006, dLng: 0.009, price_level: 2 },
                { name: 'Apex Multi-Specialty Hospital', type: 'hospital', rating: 4.9, address: 'Health City Complex, Sector 4', dLat: 0.014, dLng: -0.008, price_level: 1 },
                { name: '24/7 Global Express ATM & Bank', type: 'atm', rating: 4.3, address: 'Metro Station Plaza, Gate 2', dLat: -0.003, dLng: -0.005, price_level: 1 },
                { name: 'The Hyatt Regency Luxury Suites', type: 'lodging', rating: 4.6, address: 'Riverfront North Road', dLat: 0.011, dLng: 0.015, price_level: 3 },
                { name: 'Shell Fast EV Charging & Fuel', type: 'gas_station', rating: 4.5, address: 'Outer Ring Expressway', dLat: -0.012, dLng: -0.009, price_level: 2 },
                { name: 'FreshMarket Grocery & Bakery', type: 'supermarket', rating: 4.4, address: 'Heritage Mall Ground Level', dLat: 0.004, dLng: -0.011, price_level: 1 },
                { name: 'Urban Beans Artisan Coffee', type: 'cafe', rating: 4.6, address: 'Tech Park Avenue 3', dLat: -0.009, dLng: 0.012, price_level: 2 }
            ];

            return list
                .filter(p => !filterType || p.type === filterType)
                .map((p, idx) => ({
                    place_id: 'place_demo_' + idx + '_' + p.type,
                    name: p.name,
                    vicinity: p.address,
                    formatted_address: p.address,
                    rating: p.rating,
                    types: [p.type, 'point_of_interest'],
                    geometry: {
                        location: {
                            lat: lat + p.dLat,
                            lng: lng + p.dLng
                        }
                    },
                    opening_hours: { open_now: idx % 2 === 0 }
                }));
        }

        // Render Place Cards on Left Sidebar
        function renderPlaces(places) {
            const container = document.getElementById('places-list');
            const minRating = parseFloat(document.getElementById('rating-select').value) || 0;

            const filtered = places.filter(p => (p.rating || 0) >= minRating);
            document.getElementById('results-count-text').innerHTML = `Showing <strong class="text-slate-200">${filtered.length}</strong> places`;

            if (filtered.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12 text-slate-400">
                        <i class="fa-solid fa-map-location-dot text-3xl mb-3 text-slate-600"></i>
                        <p class="text-sm font-semibold text-slate-300">No places match your criteria</p>
                        <p class="text-xs text-slate-500 mt-1">Try broadening your radius or min rating filter.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = filtered.map((place, idx) => {
                const name = place.name || 'Unnamed Place';
                const address = place.vicinity || place.formatted_address || 'Address available upon navigation';
                const rating = place.rating ? place.rating.toFixed(1) : '4.5';
                const primaryType = (place.types && place.types[0]) ? place.types[0] : 'place';
                const icon = getCategoryIcon(primaryType);
                const placeId = place.place_id || ('id_' + idx);
                const isFav = favoritePlaceIds.has(placeId);
                const isOpen = place.opening_hours ? place.opening_hours.open_now : true;
                const lat = place.geometry?.location?.lat || currentCenter.lat;
                const lng = place.geometry?.location?.lng || currentCenter.lng;

                return `
                    <div class="bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 hover:border-indigo-500/50 rounded-xl p-3.5 transition-all shadow-sm group">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-slate-700/60 border border-slate-600/50 flex items-center justify-center text-lg shrink-0 group-hover:scale-105 transition-transform">
                                    ${icon}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-bold text-slate-100 truncate group-hover:text-indigo-300 transition-colors">${name}</h4>
                                    <p class="text-xs text-slate-400 truncate mt-0.5">${address}</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-300 font-bold text-[11px] border border-amber-500/20">
                                            ⭐ ${rating}
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded text-[11px] font-medium ${isOpen ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'}">
                                            ${isOpen ? 'Open Now' : 'Closed'}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <button onclick="toggleFavorite('${placeId}', '${escapeQuote(name)}', '${escapeQuote(address)}')" class="text-slate-400 hover:text-yellow-400 p-1.5 rounded-lg hover:bg-slate-700 transition-all shrink-0" title="Save Favorite">
                                <i class="${isFav ? 'fa-solid text-yellow-400' : 'fa-regular'} fa-star text-base"></i>
                            </button>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-slate-700/50 flex items-center justify-between text-xs">
                            <button onclick="panToPlace(${lat}, ${lng}, '${escapeQuote(name)}')" class="text-indigo-400 hover:text-indigo-300 font-medium flex items-center gap-1">
                                <i class="fa-solid fa-location-crosshairs text-[11px]"></i> View on Map
                            </button>
                            <button onclick="planRouteTo(${lat}, ${lng}, '${escapeQuote(name)}')" class="text-amber-400 hover:text-amber-300 font-medium flex items-center gap-1">
                                <i class="fa-solid fa-route text-[11px]"></i> Route Matrix
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function escapeQuote(str) {
            return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
        }

        // Render Markers on Map
        function renderMarkers(places) {
            markersGroup.clearLayers();

            places.forEach(place => {
                const lat = place.geometry?.location?.lat;
                const lng = place.geometry?.location?.lng;
                if (!lat || !lng) return;

                const primaryType = (place.types && place.types[0]) ? place.types[0] : 'place';
                const icon = getCategoryIcon(primaryType);
                const name = place.name || 'Place';
                const address = place.vicinity || place.formatted_address || '';
                const rating = place.rating ? `⭐ ${place.rating.toFixed(1)}` : '⭐ 4.5';

                const markerIcon = L.divIcon({
                    className: 'custom-pin',
                    html: `<div class="w-8 h-8 rounded-full bg-slate-900 border-2 border-indigo-500 shadow-xl flex items-center justify-center text-sm transform hover:scale-125 transition-transform">${icon}</div>`,
                    iconSize: [32, 32],
                    iconAnchor: [16, 16]
                });

                const marker = L.marker([lat, lng], { icon: markerIcon }).addTo(markersGroup);

                const popupHtml = `
                    <div class="p-1 min-w-[200px] text-slate-800">
                        <h4 class="font-bold text-sm text-slate-900">${name}</h4>
                        <p class="text-xs text-slate-600 mt-1">${address}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-xs font-bold text-amber-600">${rating}</span>
                            <button onclick="planRouteTo(${lat}, ${lng}, '${escapeQuote(name)}')" class="ml-auto text-xs px-2 py-1 bg-indigo-600 text-white rounded font-medium hover:bg-indigo-700">Route</button>
                        </div>
                    </div>
                `;
                marker.bindPopup(popupHtml);
            });
        }

        // Pan Map to Specific Place
        function panToPlace(lat, lng, name) {
            map.flyTo([lat, lng], 16, { animate: true, duration: 1.0 });
        }

        // Autocomplete / Typeahead Implementation
        const searchInput = document.getElementById('place-search-input');
        const autocompleteDropdown = document.getElementById('autocomplete-dropdown');
        const clearBtn = document.getElementById('clear-search-btn');

        searchInput.addEventListener('input', function() {
            const val = this.value.trim();
            if (val.length > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
                autocompleteDropdown.classList.add('hidden');
                return;
            }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchAutocompleteSuggestions(val);
            }, 250);
        });

        async function fetchAutocompleteSuggestions(query) {
            try {
                const res = await fetch(`/api/places/autocomplete?input=${encodeURIComponent(query)}&latitude=${currentCenter.lat}&longitude=${currentCenter.lng}`);
                const data = await res.json();

                if (data.predictions && data.predictions.length > 0) {
                    renderAutocompleteDropdown(data.predictions);
                } else {
                    autocompleteDropdown.classList.add('hidden');
                }
            } catch (e) {
                console.error('Autocomplete error:', e);
                autocompleteDropdown.classList.add('hidden');
            }
        }

        function renderAutocompleteDropdown(predictions) {
            autocompleteDropdown.innerHTML = predictions.map(p => {
                const title = p.structured_formatting ? p.structured_formatting.main_text : (p.name || p.description);
                const sub = p.structured_formatting ? p.structured_formatting.secondary_text : (p.description || '');
                const placeId = p.place_id;
                const lat = p.geometry?.location?.lat || currentCenter.lat;
                const lng = p.geometry?.location?.lng || currentCenter.lng;

                return `
                    <div onclick="selectAutocompleteItem('${placeId}', '${escapeQuote(title)}', ${lat}, ${lng})" class="px-4 py-2.5 hover:bg-slate-700/80 cursor-pointer flex items-center gap-3 transition-colors">
                        <i class="fa-solid fa-location-dot text-indigo-400 text-xs shrink-0"></i>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-slate-100 truncate">${title}</p>
                            <p class="text-[11px] text-slate-400 truncate">${sub}</p>
                        </div>
                    </div>
                `;
            }).join('');
            autocompleteDropdown.classList.remove('hidden');
        }

        function selectAutocompleteItem(placeId, title, lat, lng) {
            searchInput.value = title;
            autocompleteDropdown.classList.add('hidden');
            panToPlace(lat, lng, title);
            fetchNearbyPlaces(lat, lng);
        }

        function clearSearch() {
            searchInput.value = '';
            clearBtn.classList.add('hidden');
            autocompleteDropdown.classList.add('hidden');
            fetchNearbyPlaces(currentCenter.lat, currentCenter.lng);
        }

        function filterCategory(type) {
            fetchNearbyPlaces(currentCenter.lat, currentCenter.lng, type);
        }

        function applyFilters() {
            renderPlaces(activePlaces);
        }

        function recenterMap() {
            map.flyTo([currentCenter.lat, currentCenter.lng], 14, { animate: true });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !autocompleteDropdown.contains(e.target)) {
                autocompleteDropdown.classList.add('hidden');
            }
        });

        // ------------------ DISTANCE MATRIX CALCULATOR ------------------
        function openDistanceModal() {
            document.getElementById('distance-modal').classList.remove('hidden');
            document.getElementById('distance-modal').classList.add('flex');
            if (!document.getElementById('route-origin').value) {
                document.getElementById('route-origin').value = `${currentCenter.lat.toFixed(4)},${currentCenter.lng.toFixed(4)}`;
            }
        }

        function closeDistanceModal() {
            document.getElementById('distance-modal').classList.add('hidden');
            document.getElementById('distance-modal').classList.remove('flex');
        }

        function planRouteTo(lat, lng, name) {
            document.getElementById('route-destination').value = `${lat.toFixed(4)},${lng.toFixed(4)}`;
            document.getElementById('route-origin').value = `${currentCenter.lat.toFixed(4)},${currentCenter.lng.toFixed(4)}`;
            openDistanceModal();
            calculateRouteDistance();
        }

        function useCurrentAsOrigin() {
            document.getElementById('route-origin').value = `${currentCenter.lat.toFixed(4)},${currentCenter.lng.toFixed(4)}`;
        }

        function selectRouteMode(mode) {
            selectedRouteMode = mode;
            document.querySelectorAll('.route-mode-btn').forEach(btn => {
                btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-500');
                btn.classList.add('bg-slate-800', 'text-slate-300', 'border-slate-700');
            });
            const activeBtn = document.getElementById(`mode-btn-${mode}`);
            if (activeBtn) {
                activeBtn.classList.remove('bg-slate-800', 'text-slate-300', 'border-slate-700');
                activeBtn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-500');
            }
        }

        async function calculateRouteDistance() {
            const origin = document.getElementById('route-origin').value.trim();
            const destination = document.getElementById('route-destination').value.trim();

            if (!origin || !destination) {
                alert('Please provide both Origin and Destination');
                return;
            }

            const btn = document.getElementById('btn-calc-matrix');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Calculating...';

            try {
                const res = await fetch(`/api/places/distance?origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}&mode=${selectedRouteMode}`);
                const data = await res.json();

                btn.innerHTML = '<i class="fa-solid fa-calculator"></i> Calculate Matrix';

                if (data.success) {
                    document.getElementById('matrix-result-container').classList.remove('hidden');
                    document.getElementById('res-distance-km').innerText = data.distance.km + ' km';
                    document.getElementById('res-distance-mi').innerText = data.distance.miles + ' miles (' + data.distance.value_meters + ' m)';
                    document.getElementById('res-duration-text').innerText = data.duration.text;
                    document.getElementById('res-mode-badge').innerText = selectedRouteMode.toUpperCase();

                    // Render all modes grid
                    const allGrid = document.getElementById('all-modes-grid');
                    if (data.all_modes) {
                        allGrid.innerHTML = Object.entries(data.all_modes).map(([key, item]) => `
                            <div class="bg-slate-800/80 border ${key === selectedRouteMode ? 'border-indigo-500/80 bg-indigo-950/20' : 'border-slate-700/60'} rounded-xl p-2.5 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">${item.icon}</span>
                                    <div>
                                        <div class="font-semibold text-slate-200 capitalize">${key}</div>
                                        <div class="text-[10px] text-slate-400">${item.distance_text}</div>
                                    </div>
                                </div>
                                <div class="text-right font-bold text-amber-300">
                                    ${item.duration_text}
                                </div>
                            </div>
                        `).join('');
                    }

                    // Draw route line on Leaflet map if coordinates exist
                    if (data.origin_coords && data.destination_coords) {
                        drawRouteLine(data.origin_coords, data.destination_coords);
                    }
                }
            } catch (e) {
                console.error('Matrix calc error:', e);
                btn.innerHTML = '<i class="fa-solid fa-calculator"></i> Calculate Matrix';
            }
        }

        function drawRouteLine(origin, dest) {
            if (routeLineLayer) {
                map.removeLayer(routeLineLayer);
            }

            const latlngs = [
                [origin.lat, origin.lng],
                [dest.lat, dest.lng]
            ];

            routeLineLayer = L.polyline(latlngs, {
                color: '#6366f1',
                weight: 4,
                opacity: 0.8,
                dashArray: '8, 8'
            }).addTo(map);

            map.fitBounds(routeLineLayer.getBounds(), { padding: [50, 50] });
        }

        // ------------------ FAVORITES MANAGEMENT ------------------
        function openFavoritesDrawer() {
            document.getElementById('favorites-drawer').classList.remove('translate-x-full');
            loadFavorites();
        }

        function closeFavoritesDrawer() {
            document.getElementById('favorites-drawer').classList.add('translate-x-full');
        }

        async function loadFavorites() {
            try {
                const res = await fetch('/api/places/favorites');
                const data = await res.json();
                const list = data.data || [];

                favoritePlaceIds = new Set(list.map(f => f.place_id));
                document.getElementById('fav-count-badge').innerText = list.length;

                const container = document.getElementById('favorites-list');
                if (list.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-12 text-slate-500 text-xs">
                            <i class="fa-regular fa-star text-2xl mb-2 text-slate-600"></i>
                            <p>No favorites saved yet.</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = list.map(f => `
                    <div class="bg-slate-800 border border-slate-700/80 rounded-xl p-3 flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <h5 class="text-xs font-bold text-slate-100 truncate">${f.name}</h5>
                            <p class="text-[11px] text-slate-400 truncate mt-0.5">${f.address || 'No address'}</p>
                            <span class="text-[10px] text-slate-500 mt-1 block">${new Date(f.created_at).toLocaleDateString()}</span>
                        </div>
                        <button onclick="removeFavorite('${f.place_id}')" class="text-slate-500 hover:text-rose-400 p-1" title="Remove">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </div>
                `).join('');
            } catch (e) {
                console.error('Load favorites error:', e);
            }
        }

        async function toggleFavorite(placeId, name, address) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/api/places/favorites/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    },
                    body: JSON.stringify({
                        place_id: placeId,
                        name: name,
                        address: address
                    })
                });
                const data = await res.json();
                loadFavorites();
                renderPlaces(activePlaces);
            } catch (e) {
                console.error('Toggle favorite error:', e);
            }
        }

        async function removeFavorite(placeId) {
            try {
                await fetch(`/api/places/favorites/${placeId}`, { method: 'DELETE' });
                loadFavorites();
                renderPlaces(activePlaces);
            } catch (e) {
                console.error('Remove favorite error:', e);
            }
        }

        // ------------------ HISTORY MANAGEMENT ------------------
        function openHistoryDrawer() {
            document.getElementById('history-drawer').classList.remove('translate-x-full');
            loadHistory();
        }

        function closeHistoryDrawer() {
            document.getElementById('history-drawer').classList.add('translate-x-full');
        }

        async function loadHistory() {
            try {
                const res = await fetch('/api/places/history');
                const data = await res.json();
                const items = data.data?.data || [];

                const container = document.getElementById('history-list');
                if (items.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-12 text-slate-500 text-xs">
                            <i class="fa-solid fa-clock-rotate-left text-2xl mb-2 text-slate-600"></i>
                            <p>No recent searches yet.</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = items.map(h => `
                    <div class="bg-slate-800 border border-slate-700/80 rounded-xl p-3 flex items-center justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <h5 class="text-xs font-semibold text-slate-100 truncate">${h.query}</h5>
                            <span class="text-[10px] text-slate-500">${new Date(h.created_at).toLocaleString()}</span>
                        </div>
                        <button onclick="searchInput.value = '${escapeQuote(h.query)}'; fetchAutocompleteSuggestions('${escapeQuote(h.query)}'); closeHistoryDrawer();" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium">
                            Search
                        </button>
                    </div>
                `).join('');
            } catch (e) {
                console.error('Load history error:', e);
            }
        }

        // Init on page load
        window.addEventListener('DOMContentLoaded', () => {
            initMap();
        });
    </script>
</body>
</html>
