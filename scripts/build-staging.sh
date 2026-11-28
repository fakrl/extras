#!/usr/bin/env bash
# DB.1: bundle staging InfinityFree. Jalan di CI/lokal, TIDAK PERNAH di server.
# Pakai `git archive HEAD` (bukan working tree) biar .env, node_modules, vendor
# dev, dan file untracked lain otomatis tidak ikut.
#
# Layout default ("sibling"): isi public/ -> htdocs/, sisanya (app, bootstrap,
# config, database, resources, routes, storage, vendor) ikut di root bundle,
# SATU TINGKAT DI ATAS htdocs/ -- sesuai struktur akun InfinityFree yang
# terkonfirmasi di DB.0 (root akun berisi htdocs/ + item lain, bukan web-served
# langsung). index.php TIDAK perlu diubah: __DIR__.'/../' dari htdocs/ otomatis
# balik ke root bundle, sama seperti public/ -> project root di repo asli.
#
# Fallback ("fallback", lewat STAGING_LAYOUT=fallback): kalau upload di luar
# htdocs/ ternyata tidak memungkinkan, semua kode core ditaruh di
# htdocs/app-core/ dengan .htaccess Require all denied, dan index.php
# disesuaikan path-nya.
#
# PENTING: layout "sibling" BELUM diverifikasi langsung di panel File
# Manager InfinityFree (lihat docs/STAGING-DEPLOY.md) -- cek dulu sebelum
# dipakai, pakai fallback kalau upload di luar htdocs/ ditolak/membingungkan.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="${1:-$ROOT/dist/staging}"
LAYOUT="${STAGING_LAYOUT:-sibling}"

echo "==> Bersihkan $OUT"
rm -rf "$OUT"
mkdir -p "$OUT"

echo "==> Export tree bersih dari git HEAD (bukan working tree)"
git -C "$ROOT" archive HEAD | tar -x -C "$OUT"

echo "==> Buang yang tidak boleh ikut ke server"
rm -rf "$OUT/tests" "$OUT/.github" "$OUT/storage"

echo "==> Buang yang tidak dibutuhkan runtime (hemat jumlah file/inode InfinityFree)"
rm -rf "$OUT/whatsapp-service" "$OUT/ct-screenshots" "$OUT/docs" "$OUT/README.md" \
    "$OUT/.claude" "$OUT/.impeccable" "$OUT/AGENTS.md" "$OUT/skills-lock.json"

echo "==> composer install --no-dev (di bundle, bukan repo kerja)"
(cd "$OUT" && composer install --no-interaction --no-dev --optimize-autoloader)

echo "==> Trim vendor/: buang tests/docs/examples/.github & file non-runtime (README/LICENSE/CHANGELOG/phpunit.xml/.gitattributes/.gitignore)"
find "$OUT/vendor" -depth -type d \( -iname tests -o -iname docs -o -iname doc -o -iname examples -o -iname .github \) -exec rm -rf {} +
find "$OUT/vendor" -type f \( -iname '*.md' -o -iname 'phpunit.xml*' -o -iname '.gitattributes' -o -iname '.gitignore' -o -iname 'CHANGELOG*' -o -iname 'LICENSE*' \) -delete

echo "==> storage/ skeleton kosong (staging = data dummy, bukan isi dev lokal)"
mkdir -p "$OUT"/storage/app/public \
    "$OUT"/storage/app/private \
    "$OUT"/storage/framework/cache/data \
    "$OUT"/storage/framework/sessions \
    "$OUT"/storage/framework/testing/disks \
    "$OUT"/storage/framework/views \
    "$OUT"/storage/logs
touch "$OUT"/storage/logs/.gitkeep

echo "==> Susun htdocs/ (isi public/)"
mkdir -p "$OUT/htdocs"
shopt -s dotglob
mv "$OUT"/public/* "$OUT/htdocs/"
shopt -u dotglob
rmdir "$OUT/public"

if [ "$LAYOUT" = "fallback" ]; then
    echo "==> Layout fallback: app core di htdocs/app-core/"
    mkdir -p "$OUT/htdocs/app-core"
    for d in app bootstrap config database resources routes storage vendor artisan composer.json composer.lock; do
        [ -e "$OUT/$d" ] && mv "$OUT/$d" "$OUT/htdocs/app-core/$d"
    done
    cat > "$OUT/htdocs/app-core/.htaccess" <<'EOF'
Require all denied
EOF
    # index.php: __DIR__.'/../xxx' -> __DIR__.'/app-core/xxx'
    sed -i "s#__DIR__\.'/\.\./#__DIR__.'/app-core/#g" "$OUT/htdocs/index.php"
else
    echo "==> Layout sibling: app core tetap di root bundle (sejajar htdocs/)"
fi

echo "==> Pastikan .env TIDAK ikut ke bundle"
if find "$OUT" -maxdepth 3 -name '.env' | grep -q .; then
    echo "GAGAL: ada .env di dalam bundle, batalkan deploy." >&2
    exit 1
fi

echo "==> Cek file PHP >1MB (InfinityFree menolak upload file sebesar ini)"
BIG="$(find "$OUT" -name '*.php' -size +1M)"
if [ -n "$BIG" ]; then
    echo "GAGAL: file PHP >1MB ditemukan:" >&2
    while IFS= read -r f; do
        printf '  %s (%s)\n' "$f" "$(du -h "$f" | cut -f1)" >&2
    done <<< "$BIG"
    exit 1
fi

echo "==> Selesai. Bundle siap di: $OUT"
echo "    - $OUT/htdocs/           -> upload ke htdocs/ InfinityFree"
if [ "$LAYOUT" != "fallback" ]; then
    echo "    - $OUT/{app,bootstrap,...} -> upload ke root akun InfinityFree (SATU TINGKAT DI ATAS htdocs/)"
fi
echo "    - .env staging DIBUAT MANUAL di server (File Manager), TIDAK ada di bundle ini."
