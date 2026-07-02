#!/bin/bash
set -e

echo "=== Packaging Website Files & Assets ==="

cd /home/tatsuya/Projects/AMIS/amis_website

ARCHIVE_NAME="website_deploy.tar.gz"

tar -czf "$ARCHIVE_NAME" \
  resources/views/layouts/app.blade.php \
  resources/views/isal/halaqah.blade.php \
  public/build

echo "=== Uploading to Production ==="
scp -P 2222 "$ARCHIVE_NAME" amisdavc@50.87.224.105:/home2/amisdavc/amis.edu.ph/

echo "=== Extracting and Cleaning on Production ==="
ssh -p 2222 amisdavc@50.87.224.105 "
  cd /home2/amisdavc/amis.edu.ph && \
  tar -xzf $ARCHIVE_NAME && \
  rm $ARCHIVE_NAME && \
  php artisan view:clear && \
  php artisan config:clear
"

rm "$ARCHIVE_NAME"

echo "=== Website Deployment Finished Successfully! ==="
