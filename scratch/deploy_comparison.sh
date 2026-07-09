#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="admin_comparison_deploy.tar.gz"

echo "Bundling comparison files..."
tar -czf $ARCHIVE_NAME \
    routes/admin.php \
    app/Http/Controllers/AdminStudentController.php \
    resources/views/admin/students/comparison.blade.php \
    resources/views/admin/students/index.blade.php \
    resources/views/partials/sidebar.blade.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle on production and clearing optimize cache..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan optimize:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Comparison feature deployed successfully!"
