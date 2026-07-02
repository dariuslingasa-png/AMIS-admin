#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="student_print_deploy.tar.gz"

echo "Bundling student print and verification update files..."
tar -czf $ARCHIVE_NAME \
    routes/web.php \
    routes/admin.php \
    config/services.php \
    app/Console/Commands/AmisBackupCommand.php \
    app/Services/GoogleDriveService.php \
    app/Http/Controllers/AdminStudentController.php \
    app/Http/Controllers/PublicVerificationController.php \
    app/Http/Controllers/Admin/GoogleDriveAuthController.php \
    app/Http/Controllers/Admin/SystemManagementController.php \
    resources/views/public/verify_student.blade.php \
    resources/views/admin/system/backups/index.blade.php \
    resources/views/admin/students/index.blade.php \
    resources/views/admin/students/show.blade.php \
    resources/views/admin/students/partials/show/sidebar.blade.php \
    resources/views/admin/students/partials/index/print_info.blade.php \
    resources/views/admin/students/partials/index/print_id.blade.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle on production and clearing view cache..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan view:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Student record print updates deployed successfully!"
