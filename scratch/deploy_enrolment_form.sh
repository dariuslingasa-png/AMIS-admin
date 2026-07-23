#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="print_enrolment_form_deploy.tar.gz"

echo "=== 1. Syncing Git repository on production server via SSH ==="
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "
    cd $REMOTE_PATH && \
    git fetch origin && \
    git checkout final-school-website && \
    git reset --hard origin/final-school-website && \
    rm -f app/Http/Controllers/AdminStudentController.php app/Http/Controllers/AdminStudentDashboardController.php && \
    php artisan optimize:clear && \
    php artisan route:clear && \
    php artisan config:clear && \
    php artisan view:clear
"

rm -f $ARCHIVE_NAME

echo "=== Production Deployment Completed Successfully! ==="
