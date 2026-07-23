#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="student_records_update.tar.gz"

echo "Bundling Student Records fixes & Section column updates..."
tar -czf $ARCHIVE_NAME \
    app/Http/Controllers/Admin/StudentController.php \
    resources/views/admin/students/partials/index/table.blade.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle on production and clearing view & route caches..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan optimize:clear && php artisan view:clear && php artisan route:clear"

rm -f $ARCHIVE_NAME

echo "Student Records fixes & Section column updates deployed to production successfully!"
