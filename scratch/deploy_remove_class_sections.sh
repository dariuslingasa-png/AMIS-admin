#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="remove_class_sections_deploy.tar.gz"

echo "Bundling Class Sections deactivation modifications..."
tar -czf $ARCHIVE_NAME \
    routes/admin.php \
    resources/views/partials/sidebar.blade.php \
    resources/views/admin/academic/dashboard.blade.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle and clearing caches on production..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan optimize:clear && php artisan view:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Class Sections removed and deactivated successfully on production!"
