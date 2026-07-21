#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="halaqah_rosters_deploy.tar.gz"

echo "Bundling Halaqah Rosters integration files..."
tar -czf $ARCHIVE_NAME \
    app/Console/Commands/SyncMicrosoftTeamsRoster.php \
    app/Exceptions/MicrosoftGraphException.php \
    app/Http/Controllers/AdminMicrosoftTeamsRosterController.php \
    app/Http/Requests/ManualMicrosoftAccountMatchRequest.php \
    app/Http/Requests/UpdateMicrosoftTeamMappingRequest.php \
    app/Models/MicrosoftSyncRun.php \
    app/Models/MicrosoftTeam.php \
    app/Models/MicrosoftTeamMembership.php \
    app/Models/MicrosoftTeamSectionMapping.php \
    app/Http/Controllers/Admin/RegistrationController.php \
    app/Http/Controllers/AdminMsTeamsController.php \
    app/Http/Controllers/AdminStudentController.php \
    app/Providers/AppServiceProvider.php \
    app/Services/MicrosoftGraphService.php \
    app/Support/EnrollmentStorage.php \
    app/Services/Microsoft/ \
    app/Jobs/ \
    config/services.php \
    database/migrations/2026_07_15_000001_create_microsoft_teams_roster_sync_tables.php \
    resources/views/admin/dashboard.blade.php \
    resources/views/admin/ms-teams/index.blade.php \
    resources/views/admin/ms-teams/show.blade.php \
    resources/views/admin/registrations/halaqah.blade.php \
    resources/views/admin/students/index.blade.php \
    resources/views/admin/students/partials/index/print_id.blade.php \
    resources/views/admin/students/partials/index/print_info.blade.php \
    resources/views/admin/students/show.blade.php \
    resources/views/partials/flash.blade.php \
    resources/views/partials/sidebar.blade.php \
    resources/views/admin/microsoft-roster/ \
    routes/admin.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle on production, running migrations, and clearing caches..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && rm -f resources/views/admin/registrations/approved_halaqah.blade.php resources/views/admin/registrations/print_approved_halaqah.blade.php && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan migrate --force && php artisan optimize:clear && php artisan view:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Halaqah Rosters deployed and production database migrated successfully!"
