#!/usr/bin/env bash
# Backup de la base de datos de producción, descargado al equipo local.
#
# Uso: deploy/backup-prod-db.sh [etiqueta]
#   deploy/backup-prod-db.sh              -> backups/racingbike-2026-09-10_143210.sql.gz
#   deploy/backup-prod-db.sh pre-fix-841  -> backups/racingbike-2026-09-10_143210-pre-fix-841.sql.gz
#
# Se corre SIEMPRE antes de cualquier script que escriba en la BD de
# producción. Sin esto, un error en un script de datos es irreversible
# salvo por el backup que el hosting haga por su cuenta (que no
# controlamos ni sabemos con certeza que corra).
#
# NOTA: `wp db export` no sirve en este servidor — el hosting deshabilita
# proc_open a nivel de cuenta (no solo para el PHP web), y WP-CLI lo
# necesita para invocar mysqldump por shell. Falla "silenciosamente
# exitoso": imprime el error pero WP-CLI igual termina con código 0, así
# que un script que solo revise el exit code lo confunde con un backup
# real (nos pasó al probar esto por primera vez). Por eso se usa
# scripts/db-export.php, que exporta con $wpdb en PHP puro sin pasar por
# shell alguno.

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
source deploy/config.sh

LABEL="${1:-}"
STAMP="$(date +%Y-%m-%d_%H%M%S)"
FILENAME="racingbike-${STAMP}${LABEL:+-${LABEL}}.sql"
REMOTE_TMP="/tmp/${FILENAME}"

mkdir -p backups

echo "Subiendo el exportador..."
ssh_prod "mkdir -p ~/$REMOTE_WP_ROOT/scripts"
rsync -avz -e "ssh -i $SSH_KEY -p $SSH_PORT" \
  scripts/db-export.php \
  "$SSH_USER@$SSH_HOST:~/$REMOTE_WP_ROOT/scripts/db-export.php" > /dev/null

echo "Generando dump en el servidor (puede tardar un minuto)..."
ssh_prod "cd ~/$REMOTE_WP_ROOT && wp --skip-themes eval-file scripts/db-export.php $REMOTE_TMP"

echo ""
echo "Verificando el dump antes de confiar en él..."
REMOTE_SIZE=$(ssh_prod "stat -c %s $REMOTE_TMP 2>/dev/null || stat -f %z $REMOTE_TMP")
REMOTE_HEAD=$(ssh_prod "head -c 200 $REMOTE_TMP")

if [ "$REMOTE_SIZE" -lt 10000 ]; then
  echo "ABORTADO: el dump remoto tiene solo ${REMOTE_SIZE} bytes — demasiado pequeño para ser real." >&2
  echo "Primeras líneas:" >&2
  echo "$REMOTE_HEAD" >&2
  ssh_prod "rm -f $REMOTE_TMP" || true
  exit 1
fi

if ! echo "$REMOTE_HEAD" | grep -q "Backup generado con scripts/db-export.php"; then
  echo "ABORTADO: el dump no tiene el encabezado esperado — algo salió mal." >&2
  echo "Primeras líneas:" >&2
  echo "$REMOTE_HEAD" >&2
  ssh_prod "rm -f $REMOTE_TMP" || true
  exit 1
fi

echo "OK (${REMOTE_SIZE} bytes). Descargando y comprimiendo..."
scp -i "$SSH_KEY" -P "$SSH_PORT" "$SSH_USER@$SSH_HOST:$REMOTE_TMP" "backups/${FILENAME}"
gzip -f "backups/${FILENAME}"

echo "Borrando el temporal del servidor..."
ssh_prod "rm -f $REMOTE_TMP"

LOCAL_SIZE=$(du -h "backups/${FILENAME}.gz" | cut -f1)
echo ""
echo "Backup verificado: backups/${FILENAME}.gz (${LOCAL_SIZE})"
echo "Para restaurar: deploy/restore-prod-db.sh backups/${FILENAME}.gz"
