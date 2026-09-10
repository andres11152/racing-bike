#!/usr/bin/env bash
# Sube y corre un script PHP de scripts/ contra producción, vía wp-cli
# --skip-themes eval-file (necesario porque el PHP del sistema en el
# servidor es 8.2 y el theme exige 8.4 — cargar el theme revienta wp-cli).
#
# Modo dry-run (por defecto): sube y corre el script SIN el argumento
# "apply". Los scripts de scripts/*.php están escritos para no tocar la
# BD en ese modo — solo imprimen qué harían.
#
# Modo apply: agrega "apply" como segundo argumento. Esto SIEMPRE toma un
# backup de la BD primero (deploy/backup-prod-db.sh) — no hay forma de
# aplicar cambios sin que corra el backup antes, a propósito.
#
# Uso:
#   deploy/run-prod-script.sh scripts/fix-broken-variations.php            (dry-run)
#   deploy/run-prod-script.sh scripts/fix-broken-variations.php apply      (backup + aplica)

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
source deploy/config.sh

SCRIPT_PATH="${1:-}"
MODE="${2:-}"

if [ -z "$SCRIPT_PATH" ] || [ ! -f "$SCRIPT_PATH" ]; then
  echo "Uso: deploy/run-prod-script.sh <ruta a scripts/algo.php> [apply]" >&2
  exit 1
fi

SCRIPT_NAME="$(basename "$SCRIPT_PATH")"

if [ "$MODE" = "apply" ]; then
  echo "=== Modo APLICAR: se va a escribir en la base de datos de producción ==="
  echo ""
  deploy/backup-prod-db.sh "pre-${SCRIPT_NAME%.php}"
  echo ""
fi

echo "Subiendo ${SCRIPT_NAME} a producción..."
ssh_prod "mkdir -p ~/$REMOTE_WP_ROOT/scripts"
rsync -avz -e "ssh -i $SSH_KEY -p $SSH_PORT" \
  "$SCRIPT_PATH" \
  "$SSH_USER@$SSH_HOST:~/$REMOTE_WP_ROOT/scripts/${SCRIPT_NAME}"

echo ""
echo "Ejecutando (${MODE:-dry-run})..."
echo ""
ssh_prod "cd ~/$REMOTE_WP_ROOT && wp --skip-themes eval-file scripts/${SCRIPT_NAME} ${MODE}"
