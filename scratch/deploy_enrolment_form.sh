#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="print_enrolment_form_deploy.tar.gz"

echo "=== 1. Bundling modified files in amis_admin ==="
tar -czf $ARCHIVE_NAME \
    app/Http/Controllers/Admin/ \
    app/Http/Middleware/ApiTokenMiddleware.php \
    config/services.php \
    routes/admin.php \
    resources/views/admin/students/ \
    resources/css/ \
    public/build/ \
    public/images/logo/deped_logo.png

echo "=== 2. Uploading bundle to production server via SCP ==="
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "=== 3. Extracting bundle on production and clearing caches ==="
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "
    cd $REMOTE_PATH && \
    tar -xzf $ARCHIVE_NAME && \
    rm -f app/Http/Controllers/AdminStudentController.php app/Http/Controllers/AdminStudentDashboardController.php && \
    rm -f $ARCHIVE_NAME && \
    php artisan optimize:clear && \
    php artisan route:clear && \
    php artisan config:clear && \
    php artisan view:clear
"

rm -f $ARCHIVE_NAME

echo "=== Production Deployment Completed Successfully! ==="
