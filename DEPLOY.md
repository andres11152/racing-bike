# Cómo tocar producción

Guía corta de las herramientas en `deploy/`. Reemplaza el `ssh`/`rsync` manual
que se usaba antes — mismo resultado, un solo comando, sin depender de
acordarse de cada paso.

## Antes de cualquier cambio

- [ ] ¿Probaste el cambio en local (`docker compose up`) primero?
- [ ] Si el cambio escribe en la base de datos: ¿el script tiene modo
      dry-run y lo revisaste antes de aplicar?
- [ ] Si es un script nuevo: ¿lo probaste contra una copia local o una base
      descartable, no directo en producción?

## Comandos

**Desplegar el theme** (build + sync + limpiar caché, todo junto):
```
deploy/deploy-theme.sh
```

**Desplegar un plugin:**
```
deploy/deploy-plugin.sh skycode-wishlist
```

**Backup manual de la base de datos** (descarga a `backups/`, ignorado por git):
```
deploy/backup-prod-db.sh [etiqueta-opcional]
```

**Correr un script de datos contra producción** (`scripts/*.php`):
```
deploy/run-prod-script.sh scripts/mi-script.php          # dry-run, no escribe nada
deploy/run-prod-script.sh scripts/mi-script.php apply     # backup automático + aplica
```
El modo `apply` SIEMPRE corre `backup-prod-db.sh` primero — no hay forma de
saltárselo por accidente.

**Restaurar un backup** (destructivo, pide confirmación explícita):
```
deploy/restore-prod-db.sh backups/racingbike-2026-09-10_132347-....sql.gz
```

## Por qué no se usa `wp db export` / `wp db import`

Este hosting deshabilita `proc_open` a nivel de cuenta (no solo para el PHP
web), y esos dos comandos de WP-CLI lo necesitan para invocar `mysqldump` /
`mysql` por shell. `wp db export` además falla "silenciosamente exitoso":
imprime el error pero termina con código de salida 0, así que un script que
solo revise el exit code lo confunde con un backup real (nos pasó la primera
vez que se armó esta herramienta). Por eso `backup-prod-db.sh` usa
`scripts/db-export.php` / `scripts/db-import.php`, que exportan e importan
con `$wpdb` en PHP puro, y `backup-prod-db.sh` valida el tamaño y el
encabezado del dump antes de darlo por bueno.

## Qué NO es esto

Esto es la protección básica (backup antes de escribir + deploy repetible),
no un pipeline de CI/CD ni un ambiente de staging con datos reales. Sigue
sin haber: revisión de otra persona antes de tocar producción, ambiente
intermedio entre local y producción, ni rollback de un solo comando para el
código del theme (para la base de datos sí, vía `restore-prod-db.sh`). Si
en algún punto se vuelve necesario, son los siguientes pasos lógicos.
