#!/bin/bash
# ==========================================================
#  deploy.sh - Script Deploy Cepat & Pintar untuk Silsilah
#  Penggunaan di server:
#    bash deploy.sh             # Deploy normal (auto-detect perubahan)
#    bash deploy.sh --build     # Paksa build ulang Docker image
#    bash deploy.sh --force     # Paksa deploy meski tidak ada commit baru
# ==========================================================

set -e  # Berhenti jika ada error fatal

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${SCRIPT_DIR}"
CONTAINER_NAME=silsilah-franken
WORKER_NAME=silsilah-worker
SCHEDULER_NAME=silsilah-scheduler
BRANCH=main

# Flags
FORCE_BUILD=false
FORCE_DEPLOY=false

for arg in "$@"; do
    case $arg in
        -b|--build)
            FORCE_BUILD=true
            shift
            ;;
        -f|--force)
            FORCE_DEPLOY=true
            shift
            ;;
        -h|--help)
            echo "Penggunaan: bash deploy.sh [OPSI]"
            echo ""
            echo "Opsi:"
            echo "  -b, --build   Paksa build ulang image Docker (silsilah:latest)"
            echo "  -f, --force   Paksa deploy ulang meskipun kode sudah up-to-date"
            echo "  -h, --help    Tampilkan bantuan ini"
            exit 0
            ;;
    esac
done

echo ""
echo "=========================================="
echo "  🚀 Memulai Deploy Aplikasi Silsilah..."
echo "=========================================="
echo ""

# 1. Masuk ke direktori aplikasi
if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR"
fi
echo "📂 Direktori: $(pwd)"

# 2. Ambil commit sebelum fetch
PREV_COMMIT=$(git rev-parse HEAD 2>/dev/null || echo "")

# 3. Fetch & Pull dari GitHub
echo ""
echo "📥 [1/8] Memeriksa pembaruan dari GitHub (origin/$BRANCH)..."
git fetch origin "$BRANCH"
REMOTE_COMMIT=$(git rev-parse "origin/$BRANCH")

if [ "$PREV_COMMIT" = "$REMOTE_COMMIT" ] && [ "$FORCE_DEPLOY" = false ] && [ "$FORCE_BUILD" = false ]; then
    echo "   ℹ️  Kode di server sudah versi terbaru: $(git log -1 --oneline)"
    echo "   ✨ Tidak ada perubahan baru. Deploy dilewati."
    echo "   💡 Tips: Gunakan 'bash deploy.sh --build' jika ingin memaksa build ulang image."
    echo "            Gunakan 'bash deploy.sh --force' jika ingin memaksa deploy ulang."
    echo ""
    docker ps --filter "name=silsilah" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
    echo ""
    exit 0
fi

git reset --hard "origin/$BRANCH"
NEW_COMMIT=$(git rev-parse HEAD)
echo "   ✅ Kode diperbarui ke commit: $(git log -1 --oneline)"

# 4. Deteksi apakah perlu build ulang Docker image
NEED_BUILD=false

if [ "$FORCE_BUILD" = true ]; then
    echo "   ⚙️  Flag --build aktif: Memaksa rebuild Docker image."
    NEED_BUILD=true
elif ! docker image inspect silsilah:latest >/dev/null 2>&1; then
    echo "   ⚙️  Image silsilah:latest belum ada di server: Memerlukan build awal."
    NEED_BUILD=true
elif [ -n "$PREV_COMMIT" ] && [ "$PREV_COMMIT" != "$NEW_COMMIT" ]; then
    # Cek file-file yang memerlukan Docker image build
    CHANGED_FILES=$(git diff --name-only "$PREV_COMMIT" "$NEW_COMMIT" 2>/dev/null || echo "")
    
    # Abaikan perubahan yang hanya dokumentasi/script deploy/gitignore
    SIGNIFICANT_CHANGES=$(echo "$CHANGED_FILES" | grep -v -E '^(\.gitignore|\.github/|README\.md|PLANNING\.md|deploy\.sh|docs/)' || true)
    
    if [ -n "$SIGNIFICANT_CHANGES" ]; then
        NEED_BUILD=true
        echo "   🔍 Terdeteksi perubahan kode aplikasi / dependensi:"
        echo "$SIGNIFICANT_CHANGES" | head -n 5 | sed 's/^/      - /'
        TOTAL_CHANGED=$(echo "$SIGNIFICANT_CHANGES" | wc -l | tr -d ' ')
        if [ "$TOTAL_CHANGED" -gt 5 ]; then
            echo "      ... dan $((TOTAL_CHANGED - 5)) file lainnya."
        fi
    else
        echo "   ℹ️  Hanya file dokumentasi/deploy yang berubah, build Docker dilewati."
    fi
fi

# 5. Build Docker Image (Hanya jika diperlukan)
echo ""
if [ "$NEED_BUILD" = true ]; then
    echo "🔨 [2/8] Rebuild Docker image (silsilah:latest)..."
    # Hanya build service silsilah-franken yang mendefinisikan image silsilah:latest
    # Worker dan scheduler otomatis menggunakan image yang sama tanpa build ganda!
    docker compose build silsilah-franken
    echo "   ✅ Image silsilah:latest berhasil di-build."
else
    echo "⏭️  [2/8] Rebuild Docker image dilewati (tidak ada perubahan yang membutuhkan rebuild)."
fi

# 6. Jalankan/Perbarui Container
echo ""
echo "🔄 [3/8] Memperbarui container (web + worker + scheduler)..."
# docker compose up -d otomatis merecreate container jika image-nya berubah (zero downtime)
docker compose up -d
echo "   ✅ Container web, worker, dan scheduler aktif."

# 7. Jalankan migrasi database
echo ""
echo "🗄️  [4/8] Menjalankan migrasi database..."
docker exec "$CONTAINER_NAME" php artisan migrate --force
echo "   ✅ Migrasi selesai."

# 8. Optimasi & Cache Laravel
echo ""
echo "⚡ [5/8] Optimasi Laravel..."
docker exec "$CONTAINER_NAME" php artisan optimize:clear
docker exec "$CONTAINER_NAME" php artisan config:cache
docker exec "$CONTAINER_NAME" php artisan route:cache
docker exec "$CONTAINER_NAME" php artisan view:cache
docker exec "$CONTAINER_NAME" php artisan event:cache
docker exec "$CONTAINER_NAME" php artisan storage:link || true
echo "   ✅ Cache & Storage link berhasil disiapkan."

# 9. Restart queue worker & scheduler agar menggunakan kode terbaru
echo ""
echo "👷 [6/8] Restart Queue Worker & Scheduler..."
docker restart "$WORKER_NAME" "$SCHEDULER_NAME"
echo "   ✅ Worker & Scheduler di-restart dengan kode terbaru."

# 10. Bersihkan image Docker lama yang tidak terpakai (dangling)
echo ""
echo "🧹 [7/8] Membersihkan image Docker lama yang tidak terpakai..."
docker image prune -f
echo "   ✅ Image lama dibersihkan."

# 11. Verifikasi Status
echo ""
echo "=========================================="
echo "  ✅ Deploy Silsilah Selesai! (Port 8881)"
echo "=========================================="
echo ""
echo "📊 Status Container:"
docker ps --filter "name=silsilah" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""
