#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="gdrive_sync_deploy.tar.gz"

echo "Bundling Google Drive sync update files..."
tar -czf $ARCHIVE_NAME \
    routes/admin.php \
    app/Support/EnrollmentStorage.php \
    app/Services/GoogleDriveService.php \
    app/Services/GoogleDriveUploadService.php \
    app/Http/Controllers/AdminStudentDashboardController.php \
    app/Http/Controllers/Admin/GoogleDriveAuthController.php \
    app/Services/Admin/Enrollment/EnrollmentApprovalService.php \
    resources/views/admin/students/reports.blade.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle on production and clearing view/application caches..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan optimize:clear && php artisan view:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Google Drive sync updates deployed successfully!"
