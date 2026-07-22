#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="student_requirements_lock_deploy.tar.gz"

echo "Bundling Student Requirements Lock & LRN Audit modifications..."
tar -czf $ARCHIVE_NAME \
    resources/views/admin/students/show.blade.php \
    resources/views/admin/students/partials/show/overview.blade.php \
    resources/views/admin/students/partials/show/sidebar.blade.php \
    resources/views/admin/students/partials/show/documents.blade.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle and clearing view caches on production..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan view:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Student Requirements Lock & LRN Audit feature deployed successfully on production!"
