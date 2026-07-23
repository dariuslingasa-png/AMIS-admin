#!/bin/bash
set -e

REMOTE_USER="amisdavc"
REMOTE_HOST="50.87.224.105"
REMOTE_PORT="2222"
REMOTE_PATH="/home2/amisdavc"

echo "Deploying clean modern 503 Maintenance Page across all AMIS portals..."

# Copy 503.blade.php to server temp path
scp -o StrictHostKeyChecking=no -P $REMOTE_PORT resources/views/errors/503.blade.php $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/clean_503.blade.php

# Deploy 503.blade.php to all portal locations and clear view caches
ssh -o StrictHostKeyChecking=no -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST "
    cp $REMOTE_PATH/clean_503.blade.php $REMOTE_PATH/admin.amis.edu.ph/resources/views/errors/503.blade.php
    
    # Enrollment portal
    cp $REMOTE_PATH/clean_503.blade.php $REMOTE_PATH/enrollment.amis.edu.ph/503.blade.php
    mkdir -p $REMOTE_PATH/enrollment.amis.edu.ph/resources/views/errors
    cp $REMOTE_PATH/clean_503.blade.php $REMOTE_PATH/enrollment.amis.edu.ph/resources/views/errors/503.blade.php
    
    # Teacher portal
    mkdir -p $REMOTE_PATH/teacher.amis.edu.ph/resources/views/errors
    cp $REMOTE_PATH/clean_503.blade.php $REMOTE_PATH/teacher.amis.edu.ph/resources/views/errors/503.blade.php
    
    # Student portal
    mkdir -p $REMOTE_PATH/student.amis.edu.ph/resources/views/errors
    cp $REMOTE_PATH/clean_503.blade.php $REMOTE_PATH/student.amis.edu.ph/resources/views/errors/503.blade.php

    rm -f $REMOTE_PATH/clean_503.blade.php

    # Clear view caches across all portals
    php $REMOTE_PATH/admin.amis.edu.ph/artisan view:clear || true
    php $REMOTE_PATH/enrollment.amis.edu.ph/artisan view:clear || true
    php $REMOTE_PATH/teacher.amis.edu.ph/artisan view:clear || true
    php $REMOTE_PATH/student.amis.edu.ph/artisan view:clear || true
"

echo "Clean modern 503 Maintenance Page deployed to all portals successfully!"
