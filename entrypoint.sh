#!/bin/bash
set -e

# Tunggu sampai database tersedia
echo "Menunggu koneksi database..."
max_attempts=60
counter=0

while ! mysql -h db -u root -proot -e "SELECT 1" >/dev/null 2>&1; do
    counter=$((counter+1))
    if [ $counter -eq $max_attempts ]; then
        echo "Gagal terhubung ke database setelah $max_attempts percobaan!"
        exit 1
    fi
    echo "Menunggu database ($counter/$max_attempts)..."
    sleep 2
done

echo "Database terhubung dengan sukses!"

# Persiapan aplikasi Laravel
cd /var/www

# Buat file .env jika belum ada
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate application key jika belum ada
php artisan key:generate --no-interaction --force

# Berikan izin pada storage dan cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Jalankan migrasi database
php artisan migrate:fresh --no-interaction --force

# Jalankan database seeder jika diperlukan
php artisan db:seed --no-interaction --force

# Hapus cache konfigurasi dan rute
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Instal Node.js dependencies dan jalankan build
echo "Installing NPM dependencies..."
npm install

echo "Building Vite assets..."
npm run build

# Verifikasi bahwa manifest.json ada
if [ ! -f public/build/manifest.json ]; then
    echo "PERINGATAN: manifest.json tidak ditemukan!"
    echo "Mencoba membuat direktori build jika belum ada..."
    mkdir -p public/build
    
    echo "Mencoba menjalankan build dalam mode berbeda..."
    # Coba pilihan lain jika NPM standar gagal
    export NODE_ENV=production
    npm run build
    
    # Periksa sekali lagi apakah manifest sudah dibuat
    if [ ! -f public/build/manifest.json ]; then
        echo "ERROR: Gagal menghasilkan manifest.json!"
        echo "Memeriksa vite.config.js..."
        cat vite.config.js
        echo "Membuat manifest.json kosong untuk sementara..."
        echo "{}" > public/build/manifest.json
    fi
fi

echo "Menjalankan Laravel development server..."
exec php artisan serve --host=0.0.0.0 --port=8000