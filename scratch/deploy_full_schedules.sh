#!/bin/bash
set -e

echo "=== Deploying schedules timetable layout to production ==="

LOCAL_FILE="/home/tatsuya/Projects/AMIS/amis_admin/resources/views/admin/academic/schedules/_timetable.blade.php"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph/resources/views/admin/academic/schedules/_timetable.blade.php"

scp -P 2222 "$LOCAL_FILE" amisdavc@50.87.224.105:"$REMOTE_PATH"

echo "=== Clearing View Cache on Production ==="
ssh -p 2222 amisdavc@50.87.224.105 "
  cd /home2/amisdavc/admin.amis.edu.ph && \
  php artisan view:clear
"

echo "=== Timetable layout deployment finished successfully! ==="
