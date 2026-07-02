#!/bin/bash
set -e

echo "=== Packaging Registrations Module ==="

cd /home/tatsuya/Projects/AMIS/amis_admin

ARCHIVE_NAME="registrations_deploy.tar.gz"

tar -czf "$ARCHIVE_NAME" \
  app/Http/Controllers/Admin/RegistrationController.php \
  resources/views/admin/registrations \
  routes/admin.php \
  resources/views/partials/sidebar.blade.php \
  resources/views/admin/dashboard.blade.php

echo "=== Uploading to Production ==="
scp -P 2222 "$ARCHIVE_NAME" amisdavc@50.87.224.105:/home2/amisdavc/admin.amis.edu.ph/

echo "=== Extracting and Cleaning on Production ==="
ssh -p 2222 amisdavc@50.87.224.105 "
  cd /home2/amisdavc/admin.amis.edu.ph && \
  tar -xzf $ARCHIVE_NAME && \
  rm $ARCHIVE_NAME && \
  php artisan optimize:clear && \
  php artisan view:clear
"

rm "$ARCHIVE_NAME"

echo "=== Registrations Deployment Finished Successfully! ==="
