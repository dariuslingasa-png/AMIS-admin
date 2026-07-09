#!/bin/bash
set -e

echo "=== Deploying Schedule Teacher Dropdown Fix ==="

# Define files to upload
FILES=(
  "app/Services/Admin/Academic/TeacherMatcherService.php"
  "app/Http/Requests/Academic/StoreTeacherRequest.php"
  "resources/views/admin/academic/teachers.blade.php"
  "resources/views/admin/academic/schedules/_form-fields.blade.php"
  "resources/views/admin/academic/schedules/_inline-form.blade.php"
  "resources/views/admin/academic/schedules/_timetable.blade.php"
)

# Upload each file
for FILE in "${FILES[@]}"; do
  echo "Uploading: $FILE"
  scp -o StrictHostKeyChecking=no -P 2222 "/home/tatsuya/Projects/AMIS/amis_admin/$FILE" amisdavc@50.87.224.105:"/home2/amisdavc/admin.amis.edu.ph/$FILE"
done

echo "=== Clearing Caches on Production ==="
ssh -o StrictHostKeyChecking=no -p 2222 amisdavc@50.87.224.105 "
  cd /home2/amisdavc/admin.amis.edu.ph && \
  php artisan view:clear && \
  php artisan config:clear && \
  php artisan route:clear
"

echo "=== Deployment Finished Successfully! ==="
