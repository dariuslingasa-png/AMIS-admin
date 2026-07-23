#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="notifications_deploy.tar.gz"

echo "Bundling Real-Time Notification system files..."
tar -czf $ARCHIVE_NAME \
    routes/admin.php \
    app/Models/SystemNotification.php \
    app/Http/Controllers/Admin/SystemNotificationController.php \
    app/Http/Controllers/Admin/System/SystemBackupController.php \
    app/Console/Commands/AmisBackupCommand.php \
    resources/views/partials/topbar.blade.php \
    resources/views/admin/system/backups/index.blade.php \
    database/migrations/2026_07_23_000005_create_system_notifications_table.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle on production, running migrations, and clearing caches..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan migrate --force && php artisan optimize:clear && php artisan view:clear && php artisan route:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Real-Time Notifications feature deployed to production successfully!"
