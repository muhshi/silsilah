#!/bin/bash
# ==========================================================
#  deploy.sh - Script Deploy Cepat & Pintar untuk Silsilah
#  Penggunaan di server:
#    bash deploy.sh             # Deploy normal (auto-detect perubahan)
#    bash deploy.sh --build     # Paksa build ulang Docker image
#    bash deploy.sh --force     # Paksa deploy meski sudah up-to-date
# ==========================================================

set -e  # Berhenti jika ada error fatal

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${SCRIPT_DIR}"
CONTAINER_NAME=silsilah-franken
WORKER_NAME=silsilah-worker
SCHEDULER_NAME=silsilah-scheduler
BRANCH=main
TRACK_FILE=".last_deployed_commit"

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

# 2. Ambil commit yang terakhir aktif dideploy
LAST_DEPLOYED=$(cat "$TRACK_FILE" 2>/dev/null || echo "")

# 3. Fetch & Pull dari GitHub
echo ""
echo "📥 [1/8] Memeriksa pembaruan dari GitHub (origin/$BRANCH)..."
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"
CURRENT_COMMIT=$(git rev-parse HEAD)
echo "   ✅ Kode repository saat ini: $(git log -1 --oneline)"

# Jika commit ini sudah pernah dideploy dan container aktif berjalan
if [ "$LAST_DEPLOYED" = "$CURRENT_COMMIT" ] && [ "$FORCE_DEPLOY" = false ] && [ "$FORCE_BUILD" = false ]; then
    echo ""
    echo "   ℹ️  Kode di server ($CURRENT_COMMIT) sudah aktif dideploy sebelumnya."
    echo "   ✨ Tidak ada perubahan baru. Deploy dilewati."
    echo "   💡 Tips: Gunakan 'bash deploy.sh --build' jika ingin memaksa build ulang image."
    echo "            Gunakan 'bash deploy.sh --force' jika ingin memaksa deploy ulang."
    echo ""
    docker ps --filter "name=silsilah" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
    echo ""
    exit 0
fi

# 4. Deteksi apakah perlu build ulang Docker image
NEED_BUILD=false

if [ "$FORCE_BUILD" = true ]; then
    echo "   ⚙️  Flag --build aktif: Memaksa rebuild Docker image."
    NEED_BUILD=true
elif ! docker image inspect silsilah:latest >/dev/null 2>&1; then
    echo "   ⚙️  Image silsilah:latest belum ada di server: Memerlukan build."
    NEED_BUILD=true
elif [ -z "$LAST_DEPLOYED" ]; then
    # Belum ada catatan deploy sebelumnya (misal deploy pertama dengan script baru)
    echo "   ⚙️  Pembaruan pertama dengan sistem deploy baru: Menjalankan build image."
    NEED_BUILD=true
else
    # Cek file-file yang berubah antara commit yang sedang aktif dan commit baru
    CHANGED_FILES=$(git diff --name-only "$LAST_DEPLOYED" "$CURRENT_COMMIT" 2>/dev/null || echo "")
    
    # Abaikan perubahan yang hanya dokumentasi/script deploy/gitignore
    SIGNIFICANT_CHANGES=$(echo "$CHANGED_FILES" | grep -v -E '^(\.gitignore|\.dockerignore|\.github/|README\.md|PLANNING\.md|deploy\.sh|docs/)' || true)
    
    if [ -n "$SIGNIFICANT_CHANGES" ]; then
        NEED_BUILD=true
        echo "   🔍 Terdeteksi perubahan yang memerlukan build Docker:"
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
    # Hanya build silsilah-franken yang memproduksi silsilah:latest
    # Worker dan scheduler otomatis memakai image yang sama tanpa build ganda!
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

# 11. Simpan commit aktif yang berhasil dideploy
echo "$CURRENT_COMMIT" > "$TRACK_FILE"

# 12. Verifikasi Status
echo ""
echo "=========================================="
echo "  ✅ Deploy Silsilah Selesai! (Port 8881)"
echo "=========================================="
echo ""
echo "📊 Status Container:"
docker ps --filter "name=silsilah" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""
