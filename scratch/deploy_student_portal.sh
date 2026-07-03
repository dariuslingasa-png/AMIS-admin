#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/student.amis.edu.ph"
ARCHIVE_NAME="student_portal_deploy.tar.gz"

echo "Bundling updated layouts, app.css, and compiled Vite assets..."
tar -czf $ARCHIVE_NAME \
    routes/web.php \
    resources/views/student/layout.blade.php \
    resources/views/layouts/student.blade.php \
    resources/views/layouts/skeleton.blade.php \
    resources/views/student/teachers.blade.php \
    resources/views/student/dashboard.blade.php \
    resources/views/student/schedule.blade.php \
    resources/views/student/billing.blade.php \
    resources/css/app.css \
    app/Http/Controllers/StudentDashboardController.php \
    app/Http/Controllers/Controller.php \
    app/Http/Controllers/StudentPaymentController.php \
    app/Services/StudentPaymentService.php \
    app/Repositories/StudentRepository.php \
    app/Repositories/StudentPaymentRepository.php \
    app/Http/Requests/SubmitPaymentRequest.php \
    public/build

echo "Uploading bundle to student portal production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle on production and clearing caches..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan config:clear && php artisan cache:clear && php artisan view:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Successfully deployed Student Portal updates to production!"
