<!-- Map Component for Location Selection -->
<!-- Uses Leaflet.js with OpenStreetMap - No API key required -->

@props([
    'id' => 'map-' . uniqid(),
    'name' => 'location',
    'latitude' => old('latitude', ''),
    'longitude' => old('longitude', ''),
    'height' => '300px',
    'center' => [-6.2088, 106.8456],
    'zoom' => 13,
])

@php
    // If we have existing coordinates, use them as the center
    if ($latitude && $longitude) {
        $center = [floatval($latitude), floatval($longitude)];
        $zoom = 15;
    }
@endphp

<div class="space-y-3">
    <!-- Map Container -->
    <div id="{{ $id }}" class="rounded-lg border border-gray-300" style="height: {{ $height }};"></div>

    <!-- Hidden Inputs -->
    <input type="hidden" name="{{ $name }}_latitude" id="{{ $id }}_lat" value="{{ $latitude }}">
    <input type="hidden" name="{{ $name }}_longitude" id="{{ $id }}_lng" value="{{ $longitude }}">

    <!-- Controls -->
    <div class="flex items-center gap-2">
        <button type="button" onclick="getCurrentLocation('{{ $id }}')"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
            <i class="fa-solid fa-location-crosshairs mr-2"></i>Ambil Lokasi Saya
        </button>
        <button type="button" onclick="clearMapMarker('{{ $id }}')"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
            <i class="fa-solid fa-trash mr-2"></i>Hapus Marker
        </button>
        <span id="{{ $id }}_status" class="text-sm text-gray-500 ml-auto">
            @if($latitude && $longitude)
                <i class="fa-solid fa-check-circle text-green-600 mr-1"></i>Location set
            @else
                <i class="fa-solid fa-info-circle mr-1"></i>Klik peta atau "Ambil Lokasi"
            @endif
        </span>
    </div>

    <!-- Coordinates Display -->
    <div class="flex items-center gap-4 text-sm text-gray-600">
        <span>
            <i class="fa-solid fa-globe mr-1"></i>
            Lat: <span id="{{ $id }}_lat_display">{{ $latitude ?: '-' }}</span>
        </span>
        <span>
            <i class="fa-solid fa-globe mr-1"></i>
            Lng: <span id="{{ $id }}_lng_display">{{ $longitude ?: '-' }}</span>
        </span>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Store map instances globally
window.mapInstances = window.mapInstances || {};
window.mapMarkers = window.mapMarkers || {};

// Initialize map when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initMap('{{ $id }}');
});

function initMap(mapId) {
    // Get existing coordinates
    const latInput = document.getElementById(mapId + '_lat');
    const lngInput = document.getElementById(mapId + '_lng');
    const existingLat = latInput.value;
    const existingLng = lngInput.value;

    // Determine center
    let center = {{ json_encode($center) }};
    let zoom = {{ $zoom }};

    if (existingLat && existingLng) {
        center = [parseFloat(existingLat), parseFloat(existingLng)];
        zoom = 15;
    }

    // Initialize map
    const map = L.map(mapId).setView(center, zoom);
    window.mapInstances[mapId] = map;

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Add marker if we have existing coordinates
    if (existingLat && existingLng) {
        const marker = L.marker(center, { draggable: true }).addTo(map);
        window.mapMarkers[mapId] = marker;

        // Marker drag handler
        marker.on('dragend', function(e) {
            const latlng = e.target.getLatLng();
            updateLocation(mapId, latlng.lat, latlng.lng);
        });

        // Show popup
        marker.bindPopup('Lokasi tersimpan').openPopup();
    }

    // Click handler
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        // Update location
        updateLocation(mapId, lat, lng);

        // Add or move marker
        if (window.mapMarkers[mapId]) {
            window.mapMarkers[mapId].setLatLng(e.latlng);
        } else {
            const marker = L.marker(e.latlng, { draggable: true }).addTo(map);
            window.mapMarkers[mapId] = marker;

            marker.on('dragend', function(ev) {
                const latlng = ev.target.getLatLng();
                updateLocation(mapId, latlng.lat, latlng.lng);
            });
        }
    });
}

function updateLocation(mapId, lat, lng) {
    // Update hidden inputs
    document.getElementById(mapId + '_lat').value = lat;
    document.getElementById(mapId + '_lng').value = lng;

    // Update display
    document.getElementById(mapId + '_lat_display').textContent = lat.toFixed(6);
    document.getElementById(mapId + '_lng_display').textContent = lng.toFixed(6);
    document.getElementById(mapId + '_status').innerHTML =
        '<i class="fa-solid fa-check-circle text-green-600 mr-1"></i>Location set';
}

function getCurrentLocation(mapId) {
    const statusEl = document.getElementById(mapId + '_status');
    statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Mendeteksi lokasi...';

    if (!navigator.geolocation) {
        statusEl.innerHTML = '<i class="fa-solid fa-exclamation-triangle text-red-600 mr-1"></i>Geolocation tidak didukung browser ini';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            // Update location
            updateLocation(mapId, lat, lng);

            // Update map view
            const map = window.mapInstances[mapId];
            if (map) {
                map.setView([lat, lng], 16);

                // Add or move marker
                if (window.mapMarkers[mapId]) {
                    window.mapMarkers[mapId].setLatLng([lat, lng]);
                } else {
                    const marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                    window.mapMarkers[mapId] = marker;

                    marker.on('dragend', function(e) {
                        const latlng = e.target.getLatLng();
                        updateLocation(mapId, latlng.lat, latlng.lng);
                    });
                }

                window.mapMarkers[mapId].openPopup();
            }

            statusEl.innerHTML = '<i class="fa-solid fa-check-circle text-green-600 mr-1"></i>Location detected!';
        },
        function(error) {
            let message = 'Gagal mendapatkan lokasi';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Izin lokasi ditolak. Aktifkan di browser.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Lokasi tidak tersedia';
                    break;
                case error.TIMEOUT:
                    message = 'Waktu habis. Coba lagi.';
                    break;
            }
            statusEl.innerHTML = '<i class="fa-solid fa-exclamation-triangle text-red-600 mr-1"></i>' + message;
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

function clearMapMarker(mapId) {
    // Remove marker
    if (window.mapMarkers[mapId]) {
        window.mapMarkers[mapId].remove();
        window.mapMarkers[mapId] = null;
    }

    // Clear inputs
    document.getElementById(mapId + '_lat').value = '';
    document.getElementById(mapId + '_lng').value = '';
    document.getElementById(mapId + '_lat_display').textContent = '-';
    document.getElementById(mapId + '_lng_display').textContent = '-';
    document.getElementById(mapId + '_status').innerHTML =
        '<i class="fa-solid fa-info-circle mr-1"></i>Klik peta atau "Ambil Lokasi"';
}
</script>
@endpush
