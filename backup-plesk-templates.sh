#!/usr/bin/env bash
###############################################################################
# Plesk Templates Backup & Restore Script for Z-Push Deployment
###############################################################################
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="${SCRIPT_DIR}/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
CUSTOM_TEMPLATE_DIR="/usr/local/psa/admin/conf/templates/custom"
DEFAULT_TEMPLATE_DIR="/usr/local/psa/admin/conf/templates/default"

mkdir -p "${BACKUP_DIR}"

create_backup() {
    echo "==> Creating timestamped backup of Plesk configuration templates..."
    BACKUP_FILE="${BACKUP_DIR}/plesk_templates_backup_${TIMESTAMP}.tar.gz"
    
    # Check what exists to back up
    PATHS_TO_BACKUP=""
    if [ -d "${CUSTOM_TEMPLATE_DIR}" ]; then
        PATHS_TO_BACKUP="${CUSTOM_TEMPLATE_DIR}"
    fi
    if [ -d "${DEFAULT_TEMPLATE_DIR}" ]; then
        PATHS_TO_BACKUP="${PATHS_TO_BACKUP} ${DEFAULT_TEMPLATE_DIR}"
    fi

    if [ -n "${PATHS_TO_BACKUP}" ]; then
        tar czf "${BACKUP_FILE}" ${PATHS_TO_BACKUP} 2>/dev/null || true
        echo "==> Backup saved successfully to: ${BACKUP_FILE}"
    else
        echo "==> Warning: Plesk template directories not found. Skipping backup."
    fi
}

restore_latest_backup() {
    LATEST_BACKUP=$(ls -t "${BACKUP_DIR}"/plesk_templates_backup_*.tar.gz 2>/dev/null | head -n 1)
    if [ -z "${LATEST_BACKUP}" ]; then
        echo "==> Error: No backup files found in ${BACKUP_DIR}"
        exit 1
    fi

    echo "==> Restoring from latest backup: ${LATEST_BACKUP}..."
    tar xzf "${LATEST_BACKUP}" -C /
    echo "==> Restored successfully! Regenerating Plesk web server configuration..."
    plesk srvman reconfig || true
    systemctl reload nginx || true
    echo "==> Plesk configurations restored to backup state."
}

case "$1" in
    restore)
        restore_latest_backup
        ;;
    *)
        create_backup
        ;;
esac
