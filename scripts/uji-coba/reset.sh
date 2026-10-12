#!/usr/bin/env bash
# Data demo baru untuk walkthrough (hapus juga berkas proyek Laboratory lama).
set -euo pipefail
cd "$(dirname "$0")/../.."
rm -rf storage/app/private/lab
php artisan migrate:fresh --seed --force >/dev/null
echo "Data demo siap."
