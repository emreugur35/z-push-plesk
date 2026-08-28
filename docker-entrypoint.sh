#!/bin/bash
set -e

# Ensure permissions on log and state directories
mkdir -p /var/lib/z-push /var/log/z-push
chown -R www-data:www-data /var/lib/z-push /var/log/z-push /usr/share/z-push
chmod 755 /var/lib/z-push /var/log/z-push

# Start cron daemon for automated log rotation
service cron start

# Execute default command (apache2-foreground)
exec "$@"
