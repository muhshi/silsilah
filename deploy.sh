#!/bin/bash
# ==========================================================
#  deploy.sh - Script Deploy Otomatis untuk Silsilah
#  Penggunaan di server: bash deploy.sh
# ==========================================================

set -e  # Berhenti jika ada error

APP_DIR=~/apps/silsilah
CONTAINER_NAME=silsilah-franken
WORKER_NAME=silsilah-worker
SCHEDULER_NAME=silsilah-scheduler
BRANCH=main

echo ""
echo "=========================================="
echo "  🚀 Memulai Deploy Aplikasi Silsilah..."
echo "=========================================="
echo ""

# 1. Masuk ke direktori aplikasi (opsional jika dijalankan langsung dari folder app)
if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR"
fi
echo "📂 Direktori: $(pwd)"

# 2. Fetch & Pull dari GitHub
echo ""
echo "📥 [1/8] Mengambil kode terbaru dari GitHub..."
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"
echo "   ✅ Kode terbaru berhasil ditarik."

# 3. Build ulang Docker image
echo ""
echo "🔨 [2/8] Rebuild Docker image..."
docker compose build
echo "   ✅ Image berhasil di-build."

# 4. Restart semua container (web + worker + scheduler)
echo ""
echo "🔄 [3/8] Restart container (web + worker + scheduler)..."
docker compose down --remove-orphans
docker compose up -d
echo "   ✅ Container web, worker, dan scheduler berhasil dinyalakan."

# 5. Jalankan migrasi database
echo ""
echo "🗄️  [4/8] Menjalankan migrasi database..."
docker exec "$CONTAINER_NAME" php artisan migrate --force
echo "   ✅ Migrasi selesai."

# 6. Optimasi & Cache Laravel
echo ""
echo "⚡ [5/8] Optimasi Laravel..."
docker exec "$CONTAINER_NAME" php artisan config:cache
docker exec "$CONTAINER_NAME" php artisan route:cache
docker exec "$CONTAINER_NAME" php artisan view:cache
docker exec "$CONTAINER_NAME" php artisan event:cache
docker exec "$CONTAINER_NAME" php artisan storage:link || true
echo "   ✅ Cache & Storage link berhasil disiapkan."

# 7. Restart queue worker & scheduler
echo ""
echo "👷 [6/8] Restart Queue Worker & Scheduler..."
docker restart "$WORKER_NAME" "$SCHEDULER_NAME"
echo "   ✅ Worker & Scheduler di-restart dengan kode terbaru."

# 8. Bersihkan image Docker lama
echo ""
echo "🧹 [7/8] Membersihkan image Docker lama yang tidak terpakai..."
docker image prune -f
echo "   ✅ Image lama dibersihkan."

# 9. Verifikasi
echo ""
echo "=========================================="
echo "  ✅ Deploy Silsilah Selesai! (Port 8881)"
echo "=========================================="
echo ""
echo "📊 Status Container:"
docker ps --filter "name=silsilah" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""
