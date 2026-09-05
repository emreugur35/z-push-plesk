#!/usr/bin/env bash
###############################################################################
# Automated Deployment Script for Z-Push Docker on Plesk (Wildcard Domains)
###############################################################################
set -e

GREEN='\033[0;32m'
NC='\033[0m'

echo -e "${GREEN}==> Step 1: Starting Z-Push Docker Container...${NC}"
docker compose up -d --build

echo -e "${GREEN}==> Step 2: Creating Safety Backup of Plesk Configuration Templates...${NC}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "${SCRIPT_DIR}/backup-plesk-templates.sh" ]; then
    bash "${SCRIPT_DIR}/backup-plesk-templates.sh"
fi

echo -e "${GREEN}==> Step 3: Configuring Plesk Custom Nginx Template for Wildcard Domains...${NC}"
CUSTOM_TEMPLATE_DIR="/usr/local/psa/admin/conf/templates/custom/domain"
DEFAULT_TEMPLATE="/usr/local/psa/admin/conf/templates/default/domain/nginxDomainVirtualHost.php"

if [ -f "$DEFAULT_TEMPLATE" ]; then
    mkdir -p "$CUSTOM_TEMPLATE_DIR"
    if [ ! -f "$CUSTOM_TEMPLATE_DIR/nginxDomainVirtualHost.php" ]; then
        cp "$DEFAULT_TEMPLATE" "$CUSTOM_TEMPLATE_DIR/nginxDomainVirtualHost.php"
    fi

    # Check if Z-Push location is already added to custom template
    if ! grep -q "location /Microsoft-Server-ActiveSync" "$CUSTOM_TEMPLATE_DIR/nginxDomainVirtualHost.php"; then
        echo "Injecting Z-Push ActiveSync reverse proxy location into Plesk custom Nginx template..."
        
        # Insert before the default location / block
        sed -i '/location \/ {/i \
    location /Microsoft-Server-ActiveSync {\
        proxy_pass http://127.0.0.1:8080/Microsoft-Server-ActiveSync;\
        proxy_set_header Host $http_host;\
        proxy_set_header X-Real-IP $remote_addr;\
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;\
        proxy_set_header X-Forwarded-Proto $scheme;\
        proxy_set_header Authorization $http_authorization;\
        proxy_pass_header Authorization;\
        proxy_connect_timeout 3600s;\
        proxy_read_timeout 3600s;\
        proxy_send_timeout 3600s;\
        proxy_buffering off;\
        client_max_body_size 20M;\
    }\
' "$CUSTOM_TEMPLATE_DIR/nginxDomainVirtualHost.php"
    fi

    echo -e "${GREEN}==> Step 3: Regenerating Nginx configuration for ALL Plesk domains...${NC}"
    plesk repair web -y
    systemctl reload nginx
else
    echo "Plesk Nginx template default file not found. Applying via Plesk CLI per site..."
    cat plesk-zpush-wildcard.conf > /tmp/zpush-nginx.conf
    for site in $(plesk bin site --list); do
        plesk bin site --update "$site" -nginx-directives-file /tmp/zpush-nginx.conf || true
    done
    systemctl reload nginx
fi

echo -e "${GREEN}==> SUCCESS: Z-Push Docker is now active and proxying for ALL Plesk domains!${NC}"
