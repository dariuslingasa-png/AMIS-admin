#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="new_dashboard_deploy.tar.gz"

echo "Bundling new dashboard migration, models, controller, routes, and views..."
tar -czf $ARCHIVE_NAME \
    database/migrations/2026_06_26_044916_add_last_login_at_to_students_table.php \
    database/migrations/2026_06_26_045808_add_analytics_fields_to_students_table.php \
    database/migrations/2026_06_26_125955_add_ms_teams_activity_to_students_table.php \
    app/Models/Student.php \
    app/Services/MicrosoftGraphService.php \
    routes/admin.php \
    app/Http/Controllers/AdminStudentDashboardController.php \
    app/Console/Commands/SyncTeamMemberships.php \
    resources/views/partials/sidebar.blade.php \
    resources/views/admin/students/reports.blade.php \
    resources/views/admin/students/attendance.blade.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle, running migrations, and clearing caches on production..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan migrate --force && php artisan optimize:clear && php artisan view:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Microsoft 365 Analytics Dashboard deployed and migrated successfully on production!"
