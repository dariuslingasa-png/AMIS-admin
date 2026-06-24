#!/bin/bash
set -e

BACKUP_DIR="/home/tatsuya/Projects/AMIS/backups"
mkdir -p "$BACKUP_DIR"

TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_NAME="saved_fullsystem_${TIMESTAMP}"
TAR_PATH="${BACKUP_DIR}/${BACKUP_NAME}.tar.gz"
DB_PATH="/home/tatsuya/Projects/AMIS/database_backup_temp.sql"

echo "=== AMIS Full System Backup ==="
echo "Dumping database 'amis' using mariadb-dump..."
mariadb-dump -u amis_user -pamis123 --host=127.0.0.1 amis > "$DB_PATH"

echo "Creating tar archive of all project directories..."
cd /home/tatsuya/Projects/AMIS

tar --exclude="backups" \
    --exclude="node_modules" \
    --exclude="vendor" \
    --exclude="*/node_modules" \
    --exclude="*/vendor" \
    --exclude="amis_admin/storage/framework/cache" \
    --exclude="amis_admin/storage/logs" \
    -czf "$TAR_PATH" \
    database_backup_temp.sql \
    .

# Remove the temporary SQL file
rm "$DB_PATH"

echo "Backup complete! File saved at: $TAR_PATH"
