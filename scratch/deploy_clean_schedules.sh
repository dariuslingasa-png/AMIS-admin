#!/bin/bash
set -e

echo "=== Deploying clean schedules workspace ==="

FILES=(
  "app/Http/Controllers/AdminMsTeamsController.php"
  "resources/views/admin/academic/schedules.blade.php"
  "resources/views/admin/academic/schedules/_timetable.blade.php"
)

# Upload each file
for FILE in "${FILES[@]}"; do
  echo "Uploading: $FILE"
  scp -P 2222 "/home/tatsuya/Projects/AMIS/amis_admin/$FILE" amisdavc@50.87.224.105:"/home2/amisdavc/admin.amis.edu.ph/$FILE"
done

echo "=== Clearing View Cache on Production ==="
ssh -p 2222 amisdavc@50.87.224.105 "
  cd /home2/amisdavc/admin.amis.edu.ph && \
  php artisan view:clear
"

echo "=== Deployment Finished Successfully! ==="
