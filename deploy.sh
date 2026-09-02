#!/usr/bin/env bash
#
# Despliegue a producción — Catálogo Central de Empleados (multi-dispositivo).
#
# Uso:      ./deploy.sh
# Forzado:  DEPLOY_FORCE=1 ./deploy.sh   (omite el prompt de confirmación)
# Supervisor (detención dura de workers):
#           USE_SUPERVISOR=1 SUPERVISOR_GROUP='laravel-worker:*' ./deploy.sh
#
# Requisitos del entorno (usuario que ejecuta, sin prompts interactivos):
#   - Lectura de .env y escritura en storage/; git/composer/php/mysqldump en PATH.
#   - Con USE_SUPERVISOR=1: sudo NOPASSWD para supervisorctl. El script usa
#     sudo -n y aborta en preflight si pediría contraseña (nunca se cuelga).
#
# Orden deliberado (NO reordenar):
#   El código nuevo y las migraciones deben desplegarse JUNTOS: la migración 4
#   elimina columnas legadas (device_id, uid, role...) y el código viejo
#   fallaría al insertar. Los workers en memoria conservan código viejo hasta
#   recibir queue:restart, por eso se reinician ANTES de migrar.

set -euo pipefail

cd "$(dirname "$0")"

log() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
fail() { printf '\n\033[1;31m✗ %s\033[0m\n' "$*" >&2; exit 1; }

# Si algo falla, la app permanece en mantenimiento a propósito: reabrir con un
# esquema a medio migrar corrompería datos. Revisión manual y luego artisan up.
trap 'fail "Despliegue interrumpido. La app SIGUE en mantenimiento (php artisan up cuando se resuelva)."' ERR

log "0/9 Preflight"
grep -q '^APP_ENV=production' .env || grep -q '^APP_ENV=prod' .env || {
    [ "${DEPLOY_FORCE:-0}" = "1" ] || fail "APP_ENV no es production. Usa DEPLOY_FORCE=1 si es intencional."
}
[ -r .env ] || fail ".env no existe o el usuario actual no puede leerlo."
if [ "${USE_SUPERVISOR:-0}" = "1" ]; then
    command -v supervisorctl >/dev/null 2>&1 || fail "USE_SUPERVISOR=1 pero supervisorctl no está en PATH."
    # -n = nunca pedir contraseña: falla aquí, no a mitad del despliegue.
    sudo -n supervisorctl status >/dev/null 2>&1 || fail "sudo supervisorctl requeriría contraseña (configura NOPASSWD) o el demonio no responde."
fi
[ "${DEPLOY_FORCE:-0}" = "1" ] || read -rp "Desplegar a PRODUCCIÓN con migraciones destructivas (DROP COLUMN en employees). ¿Continuar? [y/N] " ok
[ "${ok:-y}" = "y" ] || fail "Cancelado por el operador."

log "1/9 Actualizando código"
git pull --ff-only

log "2/9 Dependencias Composer (sin dev)"
composer install --no-dev --optimize-autoloader --no-interaction

log "3/9 Modo mantenimiento"
php artisan down --render="errors::503" --retry=60

log "4/9 Deteniendo workers de colas"
if [ "${USE_SUPERVISOR:-0}" = "1" ]; then
    # Detención dura: Supervisor no relanza nada a mitad de las migraciones.
    # SUPERVISOR_GROUP evita detener programas ajenos (php-fpm, nginx) que
    # vivan en el mismo supervisord; default 'all'.
    sudo -n supervisorctl stop "${SUPERVISOR_GROUP:-all}"
else
    if php artisan list | grep -q "horizon"; then
        php artisan horizon:terminate
    else
        # Supervisor relanza los procesos; con el nuevo código ya descargado,
        # arrancarán con las clases actualizadas tras el restart.
        php artisan queue:restart
    fi
fi

log "5/9 Backup de base de datos (pre-DDL)"
DB_NAME=$(grep '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"\r')
DB_USER=$(grep '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"\r')
DB_PASS=$(grep '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"\r')
BACKUP_DIR="storage/backups"
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="$BACKUP_DIR/pre-central-catalog-$(date +%Y%m%d-%H%M%S).sql.gz"
mysqldump --user="$DB_USER" --password="$DB_PASS" \
    --single-transaction --routines --triggers --events \
    --add-drop-table \
    --no-tablespaces \
    "$DB_NAME" | gzip > "$BACKUP_FILE"
[ -s "$BACKUP_FILE" ] || fail "El backup quedó vacío: $BACKUP_FILE"
echo "   Backup: $BACKUP_FILE"

log "6/9 Migraciones de esquema y datos (M1-M4)"
php artisan migrate --force

log "7/9 Conciliación al catálogo central"
# Idempotente: si M3 ya hizo el pase completo, reporta 'ya migrado' y no toca nada.
php artisan migrate:employees-to-central --dry-run
php artisan migrate:employees-to-central

log "8/9 Reconstruyendo cachés"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

log "9/9 Reactivando aplicación"
php artisan up
if [ "${USE_SUPERVISOR:-0}" = "1" ]; then
    sudo -n supervisorctl start "${SUPERVISOR_GROUP:-all}"
fi

printf '\n\033[1;32m✔ Despliegue completado.\033[0m\n'
echo "  Backup de reversa: $BACKUP_FILE"
echo "  Siguiente paso: ejecutar docs/checklist-e2e-hardware.md contra los checadores."
