@extends('Layouts.presensi')
@section('header')
    <!-- App Header -->
    <div class="appHeader bg-primary text-light">
        <div class="left">
            <a href="javascript:;" class="headerButton goBack">
                <ion-icon name="chevron-back-outline"></ion-icon>
            </a>
        </div>
        <div class="pageTitle">E-Master</div>
        <div class="right"></div>
    </div>
    <!-- * App Header -->
    <style>
        .webcam-capture,
        .webcam-capture video{
          display: inline-block;
          width: 100% !important;
          margin: auto;
          height: auto !important;
          border-radius: 15px;
        }

        #map { 
            height: 300px; /* Increased height for better visibility */
            width: 100%;
            border-radius: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .file-upload {
            display: none;
            margin-top: 15px;
        }

        .file-upload.active {
            display: block;
        }

        .preview-image {
            max-width: 100%;
            border-radius: 15px;
            margin-top: 10px;
            display: none;
        }

        .webcam-error-message {
            padding: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 15px;
            margin-bottom: 15px;
            display: none;
        }
        
        .location-status {
            padding: 10px;
            border-radius: 10px;
            margin-top: 10px;
            display: none;
        }
        
        .location-in-range {
            background-color: #d4edda;
            color: #155724;
        }
        
        .location-out-range {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .location-info {
            font-size: 14px;
            margin-top: 5px;
        }
        
        .manual-location-container {
            margin-top: 10px;
            display: none;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 15px;
            border: 1px solid #dee2e6;
        }

        .accuracy-info {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        .map-controls {
            margin-bottom: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .office-marker {
            background-color: #3498db;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 0 5px rgba(0,0,0,0.5);
        }

        .user-marker {
            background-color: #e74c3c;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 0 5px rgba(0,0,0,0.5);
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>
@endsection
@section('content')
<div class="row" style="margin-top: 70px">
    <div class="col">
        <input type="hidden" id="lokasi">
        <input type="hidden" id="image_data">
        <div class="webcam-error-message" id="webcamError">
            Kamera tidak tersedia pada perangkat Anda. Silakan gunakan fitur upload foto sebagai alternatif.
        </div>
        <div class="webcam-capture"></div>
        <img id="previewImage" src="" alt="Preview" class="preview-image">
        <div class="file-upload" id="fileUploadContainer">
            <label for="fileInput" class="btn btn-info btn-block mt-2">
                <ion-icon name="image-outline"></ion-icon>
                Pilih Foto
            </label>
            <input type="file" id="fileInput" accept="image/*" style="display: none;">
        </div>
    </div>
</div>
<div class="row mt-2">
    <div class="col">
        <button id="takeabsen" class="btn btn-primary btn-block">
            <ion-icon name="camera-outline"></ion-icon>
            Absen Masuk
        </button>
    </div>
</div>

<!-- Map Section with improved layout -->
<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">Informasi Lokasi</h6>
            </div>
            <div class="card-body">
                <div id="map"></div>
                <div id="locationStatus" class="location-status">
                    <div id="locationMessage"></div>
                    <div id="locationInfo" class="location-info"></div>
                    <div id="accuracyInfo" class="accuracy-info"></div>
                </div>
                <div class="map-controls">
                    <button id="refreshLocation" class="btn btn-sm btn-info">
                        <ion-icon name="refresh-outline"></ion-icon>
                        Refresh Lokasi
                    </button>
                    <button id="toggleManualLocation" class="btn btn-sm btn-secondary">
                        <ion-icon name="location-outline"></ion-icon>
                        Input Lokasi Manual
                    </button>
                    <button id="zoomToUser" class="btn btn-sm btn-outline-primary">
                        <ion-icon name="person-outline"></ion-icon>
                        Tampilkan Lokasi Saya
                    </button>
                </div>
                <div id="manualLocationContainer" class="manual-location-container">
                    <div class="form-group">
                        <label for="manualLatitude">Latitude:</label>
                        <input type="text" id="manualLatitude" class="form-control" placeholder="Contoh: -7.0081276">
                    </div>
                    <div class="form-group mt-1">
                        <label for="manualLongitude">Longitude:</label>
                        <input type="text" id="manualLongitude" class="form-control" placeholder="Contoh: 113.8601780">
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <button id="setManualLocation" class="btn btn-primary">Set Lokasi</button>
                        <button id="getCurrentLocation" class="btn btn-outline-secondary">Gunakan Lokasi Saat Ini</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('myscript')
    <script>
        // Flag untuk menentukan apakah menggunakan webcam atau upload file
        let useWebcam = true;
        let webcamInitialized = false;
        
        // Lokasi kantor yang ditentukan (contoh: koordinat kantor)
        const officeLocation = {
            // -7.0081276088215185, 113.86017805092305 (Sumenep)
            latitude: -7.0081276088215185,
            longitude: 113.86017805092305,
            radius: 100 // Radius dalam meter (jarak maksimum yang diizinkan)
        };
        
        let userLocation = {
            latitude: null,
            longitude: null,
            accuracy: null
        };
        
        let map = null;
        let userMarker = null;
        let officeMarker = null;
        let radiusCircle = null;
        let accuracyCircle = null;
        let watchPositionId = null;

        // Inisialisasi webcam
        function initWebcam() {
            try {
                Webcam.set({
                    height: 480,
                    width: 640,
                    image_format: 'jpeg',
                    jpeg_quality: 80
                });

                Webcam.attach('.webcam-capture');
                webcamInitialized = true;
                
                // Periksa apakah webcam berhasil diinisialisasi setelah beberapa detik
                setTimeout(function() {
                    if (document.querySelector('.webcam-capture video') && 
                        document.querySelector('.webcam-capture video').readyState === 0) {
                        handleWebcamError();
                    }
                }, 3000);
            } catch (error) {
                handleWebcamError();
            }
        }

        // Tangani error webcam
        function handleWebcamError() {
            useWebcam = false;
            document.getElementById('webcamError').style.display = 'block';
            document.getElementById('fileUploadContainer').classList.add('active');
            
            // Jika webcam sudah diinisialisasi, lepaskan
            if (webcamInitialized) {
                try {
                    Webcam.reset();
                } catch (e) {
                    console.error("Error resetting webcam:", e);
                }
            }
            
            // Sembunyikan container webcam
            document.querySelector('.webcam-capture').style.display = 'none';
            
            // Ubah label tombol
            document.getElementById('takeabsen').innerHTML = '<ion-icon name="checkmark-outline"></ion-icon> Kirim Absensi';
        }

        // Fungsi untuk menghitung jarak antara dua titik koordinat (menggunakan formula Haversine)
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // Radius bumi dalam meter
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                    Math.cos(φ1) * Math.cos(φ2) *
                    Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c; // dalam meter
            
            return distance;
        }
        
        // Fungsi untuk memeriksa apakah lokasi pengguna dalam jangkauan
        function checkLocationInRange() {
            if (!userLocation.latitude || !userLocation.longitude) {
                return false;
            }
            
            const distance = calculateDistance(
                userLocation.latitude, userLocation.longitude,
                officeLocation.latitude, officeLocation.longitude
            );
            
            const inRange = distance <= officeLocation.radius;
            const locationStatus = document.getElementById('locationStatus');
            const locationMessage = document.getElementById('locationMessage');
            const locationInfo = document.getElementById('locationInfo');
            const accuracyInfo = document.getElementById('accuracyInfo');
            
            locationStatus.style.display = 'block';
            
            if (inRange) {
                locationStatus.className = 'location-status location-in-range';
                locationMessage.innerHTML = '<strong>Lokasi Valid!</strong> Anda berada dalam jangkauan kantor.';
            } else {
                locationStatus.className = 'location-status location-out-range';
                locationMessage.innerHTML = '<strong>Lokasi Di Luar Jangkauan!</strong> Anda berada di luar area kantor.';
            }
            
            locationInfo.innerHTML = `Jarak dari kantor: ${Math.round(distance)} meter (maksimum ${officeLocation.radius} meter)`;
            
            if (userLocation.accuracy) {
                accuracyInfo.innerHTML = `Akurasi lokasi: ±${Math.round(userLocation.accuracy)} meter`;
            }
            
            return { inRange, distance };
        }
        
        // Fungsi untuk memperbarui peta dengan lokasi dan status
        function updateMap() {
            if (!map) {
                return;
            }
            
            // Hapus marker dan lingkaran yang ada jika ada
            if (userMarker) {
                map.removeLayer(userMarker);
            }
            
            if (accuracyCircle) {
                map.removeLayer(accuracyCircle);
            }
            
            if (officeMarker) {
                map.removeLayer(officeMarker);
            }
            
            if (radiusCircle) {
                map.removeLayer(radiusCircle);
            }
            
            // Tambahkan marker kantor dengan style yang lebih bagus
            officeMarker = L.marker([officeLocation.latitude, officeLocation.longitude], {
                icon: L.divIcon({
                    className: 'office-marker',
                    html: '<div style="background-color: #3498db; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white;"></div>',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                })
            }).addTo(map);
            officeMarker.bindPopup("<b>Lokasi Kantor</b>").openPopup();
            
            // Tambahkan lingkaran radius kantor
            radiusCircle = L.circle([officeLocation.latitude, officeLocation.longitude], {
                color: '#3498db',
                fillColor: '#3498db',
                fillOpacity: 0.1,
                radius: officeLocation.radius
            }).addTo(map);
            
            if (userLocation.latitude && userLocation.longitude) {
                // Tambahkan marker pengguna dengan style yang lebih bagus
                userMarker = L.marker([userLocation.latitude, userLocation.longitude], {
                    icon: L.divIcon({
                        className: 'user-marker',
                        html: '<div style="background-color: #e74c3c; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white;"></div>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8]
                    })
                }).addTo(map);
                userMarker.bindPopup("<b>Lokasi Anda</b>");
                
                // Tambahkan lingkaran akurasi jika tersedia
                if (userLocation.accuracy) {
                    accuracyCircle = L.circle([userLocation.latitude, userLocation.longitude], {
                        color: '#e74c3c',
                        fillColor: '#e74c3c',
                        fillOpacity: 0.1,
                        radius: userLocation.accuracy
                    }).addTo(map);
                }
                
                // Set view ke posisi tengah antara user dan kantor dengan zoom yang sesuai
                const bounds = L.latLngBounds(
                    L.latLng(userLocation.latitude, userLocation.longitude),
                    L.latLng(officeLocation.latitude, officeLocation.longitude)
                );
                map.fitBounds(bounds.pad(0.3));
            } else {
                map.setView([officeLocation.latitude, officeLocation.longitude], 15);
            }
            
            // Perbarui status lokasi
            checkLocationInRange();
        }

        // Fungsi untuk mendapatkan lokasi saat ini dengan akurasi tinggi
        function getCurrentLocation() {
            // Bersihkan watch position sebelumnya jika ada
            if (watchPositionId !== null) {
                navigator.geolocation.clearWatch(watchPositionId);
                watchPositionId = null;
            }
            
            if (navigator.geolocation) {
                // Tampilkan pesan loading
                const mapElement = document.getElementById('map');
                if (!map) {
                    mapElement.innerHTML = "<div class='text-center p-3'><div class='spinner-border text-primary' role='status'></div><p class='mt-2'>Mendapatkan lokasi Anda...</p></div>";
                }
                
                // Gunakan high accuracy dan timeout yang lebih lama untuk mendapatkan lokasi lebih akurat
                const options = {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                };
                
                // Gunakan watchPosition untuk mendapatkan pembaruan lokasi yang lebih akurat
                watchPositionId = navigator.geolocation.watchPosition(
                    function(position) {
                        // Hanya perbarui jika akurasi lebih baik atau ini adalah lokasi pertama
                        if (!userLocation.accuracy || position.coords.accuracy < userLocation.accuracy) {
                            userLocation.latitude = position.coords.latitude;
                            userLocation.longitude = position.coords.longitude;
                            userLocation.accuracy = position.coords.accuracy;
                            
                            // Update input lokasi
                            document.getElementById('lokasi').value = `${position.coords.latitude},${position.coords.longitude}`;
                            
                            // Update nilai di form manual
                            document.getElementById('manualLatitude').value = position.coords.latitude.toFixed(7);
                            document.getElementById('manualLongitude').value = position.coords.longitude.toFixed(7);
                            
                            // Inisialisasi peta jika belum ada
                            initMap();
                            
                            // Update peta dengan lokasi
                            updateMap();
                            
                            // Hentikan watch setelah 5 detik untuk menghemat baterai
                            setTimeout(function() {
                                if (watchPositionId !== null) {
                                    navigator.geolocation.clearWatch(watchPositionId);
                                    watchPositionId = null;
                                }
                            }, 5000);
                        }
                    },
                    errorCallback,
                    options
                );
            } else {
                const mapElement = document.getElementById('map');
                mapElement.innerHTML = "<p class='text-danger'>Geolokasi tidak didukung oleh browser Anda.</p>";
                
                // Tampilkan opsi lokasi manual
                document.getElementById('manualLocationContainer').style.display = 'block';
            }
        }

        // Inisialisasi peta
        function initMap() {
            if (!map) {
                map = L.map('map');
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(map);
                
                // Tambahkan kontrol skala
                L.control.scale({imperial: false}).addTo(map);
            }
            
            return map;
        }

        function successCallback(position) {
            userLocation.latitude = position.coords.latitude;
            userLocation.longitude = position.coords.longitude;
            userLocation.accuracy = position.coords.accuracy;
            
            // Update input lokasi
            document.getElementById('lokasi').value = `${position.coords.latitude},${position.coords.longitude}`;
            
            // Update nilai di form manual
            document.getElementById('manualLatitude').value = position.coords.latitude.toFixed(7);
            document.getElementById('manualLongitude').value = position.coords.longitude.toFixed(7);
            
            // Inisialisasi peta jika belum ada
            initMap();
            
            // Update peta dengan lokasi
            updateMap();
        }

        function errorCallback(error) {
            console.error("Error getting location: ", error.message);
            const mapElement = document.getElementById('map');
            const errorMessages = {
                1: "Akses lokasi ditolak. Mohon izinkan akses lokasi pada browser Anda.",
                2: "Lokasi tidak tersedia. Coba gunakan fitur lokasi manual.",
                3: "Waktu mendapatkan lokasi habis. Coba tingkatkan akurasi GPS atau gunakan lokasi manual."
            };
            
            const errorMessage = errorMessages[error.code] || "Tidak dapat mengakses lokasi Anda.";
            
            // Inisialisasi peta jika belum ada
            if (!map) {
                initMap();
                map.setView([officeLocation.latitude, officeLocation.longitude], 15);
                updateMap();
            }
            
            // Tampilkan pesan error
            const locationStatus = document.getElementById('locationStatus');
            const locationMessage = document.getElementById('locationMessage');
            
            locationStatus.style.display = 'block';
            locationStatus.className = 'location-status location-out-range';
            locationMessage.innerHTML = `<strong>Error:</strong> ${errorMessage}`;
            
            // Tampilkan opsi lokasi manual
            document.getElementById('manualLocationContainer').style.display = 'block';
        }
        
        // Fungsi untuk menetapkan lokasi manual
        function setManualLocation() {
            const latitude = parseFloat(document.getElementById('manualLatitude').value);
            const longitude = parseFloat(document.getElementById('manualLongitude').value);
            
            if (isNaN(latitude) || isNaN(longitude)) {
                alert('Silakan masukkan nilai latitude dan longitude yang valid.');
                return;
            }
            
            userLocation.latitude = latitude;
            userLocation.longitude = longitude;
            userLocation.accuracy = 10; // Asumsi akurasi 10 meter untuk lokasi manual
            
            document.getElementById('lokasi').value = `${latitude},${longitude}`;
            
            // Inisialisasi peta jika belum ada
            initMap();
            
            // Update peta
            updateMap();
        }

        // Fungsi untuk zoom ke lokasi pengguna
        function zoomToUser() {
            if (userLocation.latitude && userLocation.longitude && map) {
                map.setView([userLocation.latitude, userLocation.longitude], 17);
            }
        }

        // Coba inisialisasi webcam
        initWebcam();
        
        // Dapatkan lokasi saat ini
        getCurrentLocation();

        // Event listener untuk upload file
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('image_data').value = event.target.result;
                    document.getElementById('previewImage').src = event.target.result;
                    document.getElementById('previewImage').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Event listener untuk tombol absen
        document.getElementById('takeabsen').addEventListener('click', function() {
            if (useWebcam) {
                // Menggunakan webcam
                Webcam.snap(function(data_uri) {
                    document.getElementById('image_data').value = data_uri;
                    kirimData();
                });
            } else {
                // Menggunakan upload file
                if (document.getElementById('image_data').value) {
                    kirimData();
                } else {
                    alert('Silakan pilih foto terlebih dahulu!');
                }
            }
        });
        
        // Event listener untuk tombol refresh lokasi
        document.getElementById('refreshLocation').addEventListener('click', function() {
            getCurrentLocation();
        });
        
        // Event listener untuk toggle lokasi manual
        document.getElementById('toggleManualLocation').addEventListener('click', function() {
            const manualLocationContainer = document.getElementById('manualLocationContainer');
            if (manualLocationContainer.style.display === 'none' || manualLocationContainer.style.display === '') {
                manualLocationContainer.style.display = 'block';
            } else {
                manualLocationContainer.style.display = 'none';
            }
        });
        
        // Event listener untuk tombol zoom ke user
        document.getElementById('zoomToUser').addEventListener('click', zoomToUser);
        
        // Event listener untuk tombol set lokasi manual
        document.getElementById('setManualLocation').addEventListener('click', setManualLocation);
        
        // Event listener untuk tombol gunakan lokasi saat ini
        document.getElementById('getCurrentLocation').addEventListener('click', getCurrentLocation);

        // Fungsi untuk mengirim data
        function kirimData() {
            const imageData = document.getElementById('image_data').value;
            const lokasiData = document.getElementById('lokasi').value;
            
            if (!lokasiData) {
                alert('Lokasi tidak ditemukan. Mohon aktifkan akses lokasi pada browser atau gunakan fitur lokasi manual.');
                return;
            }
            
            // Periksa jarak lokasi
            const locationCheck = checkLocationInRange();
            
            if (!locationCheck.inRange) {
                if (!confirm(`Anda berada di luar jangkauan kantor (${Math.round(locationCheck.distance)} meter dari kantor). Tetap lanjutkan absensi?`)) {
                    return;
                }
            }
            console.log({
                    foto: imageData,
                    lokasi: lokasiData,
                    jarak_dari_kantor: Math.round(locationCheck.distance),
                    lokasi_valid: locationCheck.inRange,
                    akurasi: userLocation.accuracy ? Math.round(userLocation.accuracy) : null
                });
            
            // Di sini Anda bisa menambahkan kode untuk mengirim data ke server
            // Contoh menggunakan fetch:
            
            /*
            fetch('/api/absensi', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                
                body: JSON.stringify({
                    foto: imageData,
                    lokasi: lokasiData,
                    jarak_dari_kantor: Math.round(locationCheck.distance),
                    lokasi_valid: locationCheck.inRange,
                    akurasi: userLocation.accuracy ? Math.round(userLocation.accuracy) : null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Absensi berhasil disimpan!');
                    window.location.href = '/dashboard';
                } else {
                    alert('Terjadi kesalahan: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengirim data.');
            });
            */
            
            // Untuk contoh saja, tampilkan alert
            alert('Data absensi siap dikirim!\nFoto: ' + (imageData ? 'Ada' : 'Tidak ada') + 
                  '\nLokasi: ' + lokasiData + 
                  '\nJarak dari kantor: ' + Math.round(locationCheck.distance) + ' meter' +
                  '\nLokasi valid: ' + (locationCheck.inRange ? 'Ya' : 'Tidak') +
                  '\nAkurasi: ' + (userLocation.accuracy ? `±${Math.round(userLocation.accuracy)} meter` : 'Tidak diketahui'));
        }
    </script> 
@endpush