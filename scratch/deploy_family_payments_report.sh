#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="deploy_family_payments_report.tar.gz"

echo "Bundling files for Family Payments Report..."
tar -czf $ARCHIVE_NAME \
    app/Http/Controllers/AdminPaymentController.php \
    app/Console/Commands/SendFinanceReports.php \
    resources/views/admin/payments/print-report.blade.php \
    resources/views/admin/payments/pdf-report.blade.php \
    resources/views/admin/payments/index.blade.php \
    routes/admin.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle on production and clearing caches..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan optimize:clear && php artisan view:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Family Payments Report deployed successfully!"
