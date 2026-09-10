#!/usr/bin/env bash
# Restaura un backup generado por deploy/backup-prod-db.sh en producción.
# DESTRUCTIVO: reemplaza todas las tablas actuales por las del backup.
#
# Uso: deploy/restore-prod-db.sh backups/racingbike-2026-09-10_132028.sql.gz

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
source deploy/config.sh

BACKUP_FILE="${1:-}"

if [ -z "$BACKUP_FILE" ] || [ ! -f "$BACKUP_FILE" ]; then
  echo "Uso: deploy/restore-prod-db.sh <ruta al .sql.gz en backups/>" >&2
  exit 1
fi

echo "=== Vas a restaurar producción con: $BACKUP_FILE ==="
echo "Esto BORRA y reemplaza TODAS las tablas actuales. No hay deshacer."
read -r -p "Escribe CONFIRMO para continuar: " CONFIRM

if [ "$CONFIRM" != "CONFIRMO" ]; then
  echo "Cancelado."
  exit 1
fi

REMOTE_SQL="/tmp/restore-$(date +%s).sql"

echo "Subiendo el restaurador..."
rsync -avz -e "ssh -i $SSH_KEY -p $SSH_PORT" \
  scripts/db-import.php \
  "$SSH_USER@$SSH_HOST:~/$REMOTE_WP_ROOT/scripts/db-import.php" > /dev/null

echo "Descomprimiendo y subiendo el dump..."
gunzip -c "$BACKUP_FILE" > /tmp/rb-restore-upload.sql
scp -i "$SSH_KEY" -P "$SSH_PORT" /tmp/rb-restore-upload.sql "$SSH_USER@$SSH_HOST:$REMOTE_SQL"
rm -f /tmp/rb-restore-upload.sql

echo "Restaurando..."
ssh_prod "cd ~/$REMOTE_WP_ROOT && wp --skip-themes eval-file scripts/db-import.php $REMOTE_SQL CONFIRMO"

ssh_prod "rm -f $REMOTE_SQL"
clear_acorn_view_cache

echo ""
echo "Restauración terminada."
