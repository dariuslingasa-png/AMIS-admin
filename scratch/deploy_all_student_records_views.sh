#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="all_student_records_views_deploy.tar.gz"

echo "Bundling all student records views..."
tar -czf $ARCHIVE_NAME \
    resources/views/admin/students/index.blade.php \
    resources/views/admin/students/partials/index/filters.blade.php \
    resources/views/admin/students/partials/index/print.blade.php \
    resources/views/admin/students/partials/index/table.blade.php \
    resources/views/admin/students/partials/index/telemetry.blade.php \
    resources/views/admin/students/partials/index/print_id.blade.php \
    resources/views/admin/students/partials/index/print_info.blade.php \
    resources/views/admin/students/partials/index/print_credentials.blade.php \
    resources/views/admin/students/partials/index/print_list.blade.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle on production and clearing view cache..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan view:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "All student records views deployed successfully!"
