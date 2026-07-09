#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"

echo "Syncing files to production using rsync..."
rsync -avz -e "ssh -p $REMOTE_PORT -o StrictHostKeyChecking=no" \
    app/Console/Commands/SendFinanceReports.php \
    app/Http/Controllers/AdminPaymentController.php \
    resources/views/admin/payments/print-report.blade.php \
    resources/views/admin/payments/pdf-report.blade.php \
    resources/views/admin/payments/index.blade.php \
    routes/admin.php \
    $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Syncing vendor folders..."
rsync -avz -e "ssh -p $REMOTE_PORT -o StrictHostKeyChecking=no" \
    vendor/dompdf \
    vendor/masterminds \
    vendor/sabberworm \
    vendor/thecodingmachine \
    $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/vendor/

echo "Clearing caches on production..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && php artisan optimize:clear && php artisan view:clear"

echo "Deploy complete!"
