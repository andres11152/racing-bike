#!/usr/bin/env bash
# Conexión compartida al servidor de producción. Todos los scripts de
# deploy/ hacen `source` de este archivo en vez de repetir estos valores
# — cambiar de servidor o de llave es un solo lugar, no uno por script.
#
# No pone credenciales en texto plano: apunta a una llave SSH que vive en
# ~/.ssh (fuera del repo) y que el servidor ya tiene autorizada.

set -euo pipefail

SSH_KEY="${SSH_KEY:-$HOME/.ssh/claude-code-deploy}"
SSH_PORT="65002"
SSH_HOST="92.112.189.172"
SSH_USER="u114746944"
REMOTE_WP_ROOT="domains/racingbike.com.co/public_html"

ssh_prod() {
  ssh -i "$SSH_KEY" -p "$SSH_PORT" -o BatchMode=yes "$SSH_USER@$SSH_HOST" "$@"
}

rsync_to_prod() {
  # Uso: rsync_to_prod <origen local> <ruta relativa a REMOTE_WP_ROOT>
  local src="$1"
  local dest="$2"
  rsync -avz --checksum -e "ssh -i $SSH_KEY -p $SSH_PORT" \
    "$src" "$SSH_USER@$SSH_HOST:~/$REMOTE_WP_ROOT/$dest"
}

clear_acorn_view_cache() {
  ssh_prod "rm -f ~/$REMOTE_WP_ROOT/wp-content/cache/acorn/framework/views/*.php" || true
}

# Limpia el caché de PÁGINA COMPLETA de LiteSpeed Cache — distinto del de
# vistas de Acorn de arriba. Detectado el 2026-09-17: tras desplegar el
# rediseño del filtro de precio, /tienda/ seguía sirviendo el HTML viejo
# (dos <input type="number"> en vez del slider) durante varios minutos
# porque LiteSpeed ya tenía esa página en caché desde antes del deploy, y
# clear_acorn_view_cache solo limpia las plantillas Blade compiladas, no
# el HTML final cacheado. Sin esto, cada cambio visible en el frontend
# necesita una purga manual aparte para verse de inmediato.
purge_litespeed_cache() {
  ssh_prod "cd ~/$REMOTE_WP_ROOT && wp --skip-themes litespeed-purge all" || true
}
