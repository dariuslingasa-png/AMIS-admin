#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="print_enrolment_form_deploy.tar.gz"

echo "=== 1. Bundling modified files in amis_admin ==="
tar -czf $ARCHIVE_NAME \
    app/Http/Controllers/AdminStudentController.php \
    app/Http/Controllers/AdminStudentDashboardController.php \
    routes/admin.php \
    resources/views/admin/students/index.blade.php \
    resources/views/admin/students/partials/index/table.blade.php \
    resources/views/admin/students/partials/show/sidebar.blade.php \
    resources/views/admin/students/print-enrolment-form.blade.php \
    resources/views/admin/students/print-enrolment-form-batch.blade.php \
    resources/views/admin/students/partials/print/enrolment-form-body.blade.php \
    resources/views/admin/students/occupancy.blade.php \
    resources/views/admin/students/partials/occupancy/card.blade.php \
    public/images/logo/deped_logo.png

echo "=== 2. Uploading bundle to production server via SCP ==="
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "=== 3. Extracting bundle on production and clearing caches ==="
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "
    cd $REMOTE_PATH && \
    tar -xzf $ARCHIVE_NAME && \
    rm $ARCHIVE_NAME && \
    php artisan optimize:clear && \
    php artisan route:clear && \
    php artisan config:clear && \
    php artisan view:clear
"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "=== Print Enrollment Application Form feature deployed successfully to production! ==="
