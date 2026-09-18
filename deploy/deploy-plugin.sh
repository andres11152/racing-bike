#!/usr/bin/env bash
# Despliega un plugin a producción y limpia la caché de vistas.
#
# Uso: deploy/deploy-plugin.sh <carpeta-del-plugin>
#   deploy/deploy-plugin.sh skycode-wishlist
#   deploy/deploy-plugin.sh skycode-smtp

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
source deploy/config.sh

PLUGIN="${1:-}"

if [ -z "$PLUGIN" ]; then
  echo "Uso: deploy/deploy-plugin.sh <carpeta-del-plugin>" >&2
  exit 1
fi

if [ ! -d "$PLUGIN" ]; then
  echo "No existe la carpeta '$PLUGIN' en la raíz del proyecto." >&2
  exit 1
fi

echo "Sincronizando ${PLUGIN} a producción..."
rsync -avz --checksum -e "ssh -i $SSH_KEY -p $SSH_PORT" \
  --exclude '.git' --exclude '.DS_Store' \
  "${PLUGIN}/" \
  "$SSH_USER@$SSH_HOST:~/$REMOTE_WP_ROOT/wp-content/plugins/${PLUGIN}/"

clear_acorn_view_cache
purge_litespeed_cache

echo ""
echo "Plugin '${PLUGIN}' desplegado."
