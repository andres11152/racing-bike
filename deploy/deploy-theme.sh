#!/usr/bin/env bash
# Despliega el theme completo a producción: build de assets, sync de
# archivos (por checksum, solo lo que cambió), y limpieza de caché de
# vistas de Acorn. Reemplaza el `rsync` manual que se repetía a mano en
# cada cambio — un solo comando, mismo resultado siempre.
#
# Uso: deploy/deploy-theme.sh

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
source deploy/config.sh

echo "1/3 Compilando assets (vite build)..."
(cd racing-bike-theme && npx vite build)

echo "2/3 Sincronizando archivos a producción..."
rsync -avz --checksum -e "ssh -i $SSH_KEY -p $SSH_PORT" \
  --exclude 'node_modules' --exclude '.git' --exclude 'vendor' --exclude '.DS_Store' \
  racing-bike-theme/ \
  "$SSH_USER@$SSH_HOST:~/$REMOTE_WP_ROOT/wp-content/themes/racing-bike-theme/"

echo "3/3 Limpiando caché de vistas..."
clear_acorn_view_cache

echo ""
echo "Theme desplegado."
