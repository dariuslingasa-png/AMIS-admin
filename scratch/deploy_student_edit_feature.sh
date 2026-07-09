#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="student_edit_feature_deploy.tar.gz"

echo "=== 1. Bundling student edit feature files ==="
tar -czf $ARCHIVE_NAME \
    app/Http/Controllers/AdminStudentController.php \
    routes/admin.php \
    resources/views/admin/students/show.blade.php \
    resources/views/components/applicant/detail-section.blade.php

echo "=== 2. Uploading bundle to production server ==="
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "=== 3. Extracting bundle on production and clearing caches ==="
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "
    cd $REMOTE_PATH && \
    tar -xzf $ARCHIVE_NAME && \
    rm $ARCHIVE_NAME && \
    php artisan optimize:clear && \
    php artisan route:clear && \
    php artisan route:cache && \
    php artisan view:clear && \
    php artisan view:cache
"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "=== Student edit feature deployed successfully to AMIS Admin Portal! ==="
