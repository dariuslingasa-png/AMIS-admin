#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="student_requirements_lock_deploy.tar.gz"

echo "Bundling Student Requirements Lock & Old Student Exemption modifications..."
tar -czf $ARCHIVE_NAME \
    database/migrations/2026_07_22_000001_add_is_requirements_locked_to_students_table.php \
    app/Models/Student.php \
    app/Http/Controllers/AdminStudentController.php \
    routes/admin.php \
    resources/views/admin/students/show.blade.php \
    resources/views/admin/students/partials/show/overview.blade.php \
    resources/views/admin/students/partials/show/sidebar.blade.php \
    resources/views/admin/students/partials/show/documents.blade.php \
    resources/views/components/applicant/detail-section.blade.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle, running migrations, and clearing view caches on production..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan migrate --force && php artisan view:clear && php artisan route:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Student Requirements Lock & Old Student Exemption features deployed successfully on production!"
