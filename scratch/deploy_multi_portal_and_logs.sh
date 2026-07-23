#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="multi_portal_logs_deploy.tar.gz"

echo "Bundling Multi-Portal Maintenance and Log Viewer files..."
tar -czf $ARCHIVE_NAME \
    app/Http/Controllers/Admin/System/SystemDevOpsController.php \
    app/Services/System/SystemDevOpsService.php \
    resources/views/admin/system/devops/index.blade.php \
    resources/views/admin/system/logs/index.blade.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle on production and clearing view & route caches..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan optimize:clear && php artisan view:clear && php artisan route:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Multi-Portal Maintenance Mode & System Logs upgrades deployed to production successfully!"
