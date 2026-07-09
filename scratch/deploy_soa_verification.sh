#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc/admin.amis.edu.ph"
ARCHIVE_NAME="soa_verification_deploy.tar.gz"

echo "Bundling updated SOA verification files..."
tar -czf $ARCHIVE_NAME \
    app/Http/Controllers/AdminSoaController.php \
    resources/views/admin/soa/partials/show-payment-history.blade.php \
    resources/views/admin/soa/partials/show-payment-modal-script.blade.php

echo "Uploading bundle to production server..."
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT $ARCHIVE_NAME $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "Extracting bundle and clearing caches on production..."
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && tar -xzf $ARCHIVE_NAME && rm $ARCHIVE_NAME && php artisan optimize:clear && php artisan view:clear"

# Clean up local archive
rm -f $ARCHIVE_NAME

echo "Overall SOA payment verification workflow successfully deployed to production!"
