#!/usr/bin/env bash
set -e

echo "🚀 [Deploy] Memulai deployment aplikasi SCP SMK..."

# 1. Pastikan di direktori proyek
cd "$(dirname "$0")"

# 2. Tarik kode terbaru dari repository
echo "📥 [Deploy] Menarik pembaruan Git terbaru..."
git pull origin main

# 3. Install dependency PHP (Composer) tanpa dev dependencies
echo "📦 [Deploy] Menginstall Composer dependencies..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --ignore-platform-req=php

# 4. Install dependency Frontend (Node/NPM) & Build Vite
echo "🎨 [Deploy] Menginstall NPM packages & building Vite assets..."
npm ci || npm install
npm run build

# 5. Setup Python Virtual Environment & Install dependencies (Kompatibel Ubuntu 22.04 / 24.04+)
if [ -f "requirements.txt" ]; then
    echo "🐍 [Deploy] Menyiapkan environment Python..."
    
    # Buat virtual environment jika belum ada
    if [ ! -d "venv" ]; then
        echo "🐍 [Deploy] Membuat virtual environment (venv)..."
        python3 -m venv venv 2>/dev/null || \
        (command -v sudo >/dev/null 2>&1 && sudo apt-get update -y && sudo apt-get install -y python3-venv python3-pip && python3 -m venv venv) || true
    fi

    # Install modul via venv jika tersedia
    if [ -f "venv/bin/pip" ]; then
        echo "🐍 [Deploy] Menginstall requirements ke dalam venv..."
        ./venv/bin/pip install --upgrade pip --quiet
        ./venv/bin/pip install -r requirements.txt --quiet
        chmod -R a+rX venv/ 2>/dev/null || true
    else
        echo "⚠️ [Deploy] Venv tidak tersedia, mencoba instalasi system-wide..."
        pip3 install --break-system-packages -r requirements.txt --quiet 2>/dev/null || \
        pip3 install -r requirements.txt --quiet 2>/dev/null || true
    fi
fi

# 6. Migrasi database production
echo "🗄️ [Deploy] Menjalankan migrasi database..."
php artisan migrate --force

# 7. Pastikan storage link tersedia
echo "🔗 [Deploy] Menautkan storage publik..."
php artisan storage:link || true

# 8. Optimasi cache rute, config, dan view
echo "⚡ [Deploy] Mengoptimasi cache Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 9. Restart queue worker (jika menggunakan queue background)
echo "🔄 [Deploy] Merestart antrean queue..."
php artisan queue:restart || true

echo "✅ [Deploy] Deployment selesai dengan sukses!"

