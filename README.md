# Z-Push Docker for Plesk (Wildcard Domain Support)

This repository provides a production-ready, Dockerized Z-Push solution for Plesk servers. Instead of manually copying Z-Push files into each domain's `httpdocs` directory and editing configurations individually, this deployment runs **a single containerized Z-Push instance** listening locally and reverse-proxies ActiveSync requests (`/Microsoft-Server-ActiveSync`) for **ALL Plesk hosted domains automatically**.

---

## Features

- **Wildcard Multi-Domain Support**: Single container handles ActiveSync for every domain on Plesk.
- **Plesk IMAP/SMTP Integration**: Authenticates directly against Plesk's Dovecot IMAP and Postfix SMTP (`USE_FULLEMAIL_FOR_LOGIN = true`).
- **PHP 8.2 Base**: Lightweight Apache + PHP 8.2 container with all required extensions (`imap`, `intl`, `pcntl`, `posix`, `sysvsem`, `sysvshm`, `bcmath`).
- **Configurable via Environment**: Easily customize IMAP server host, ports, timezone, log levels, and state directories.
- **Automated Plesk Wildcard Integration**: Script automatically updates Plesk's custom Nginx template and regenerates server configurations.

---

## Project Files

| File | Description |
| :--- | :--- |
| [`Dockerfile`](file:///Users/emreugur/z-push-docker/Dockerfile) | PHP 8.2 + Apache image with Z-Push 2.7.6 and required extensions |
| [`docker-compose.yml`](file:///Users/emreugur/z-push-docker/docker-compose.yml) | Docker Compose service definition on port `127.0.0.1:8080` |
| [`config.php`](file:///Users/emreugur/z-push-docker/config.php) | Z-Push main config with dynamic environment variable support |
| [`imap.conf.php`](file:///Users/emreugur/z-push-docker/imap.conf.php) | Z-Push IMAP backend config for Plesk mail authentication |
| [`apache-zpush.conf`](file:///Users/emreugur/z-push-docker/apache-zpush.conf) | Apache virtualhost configuration inside the container |
| [`plesk-zpush-wildcard.conf`](file:///Users/emreugur/z-push-docker/plesk-zpush-wildcard.conf) | Nginx location directives for Plesk proxying |
| [`deploy-plesk-wildcard.sh`](file:///Users/emreugur/z-push-docker/deploy-plesk-wildcard.sh) | One-touch automated deployment script for Plesk servers |
| [`backup-plesk-templates.sh`](file:///Users/emreugur/z-push-docker/backup-plesk-templates.sh) | Creates timestamped safety backups of Plesk configuration templates (with restore option) |

---

## Quick Start / Deployment Instructions

### 1. Clone or Upload to Plesk Host
Upload this repository to `/opt/z-push-docker` or any directory on your Plesk server:

```bash
git clone <your-repo-url> /opt/z-push-docker
cd /opt/z-push-docker
```

### 2. Run Automated Deployment
Execute the deployment script with root privileges:

```bash
chmod +x deploy-plesk-wildcard.sh
./deploy-plesk-wildcard.sh
```

This script will:
1. Build and start the `z-push-plesk` Docker container.
2. Inject the `/Microsoft-Server-ActiveSync` proxy rules into Plesk's Nginx custom domain template (`/usr/local/psa/admin/conf/templates/custom/domain/nginxDomainVirtualHost.php`).
3. Run `plesk repair web -y` to apply the changes to **all current and future domains**.

---

## Manual Deployment Steps

If you prefer manual installation:

1. **Start the Docker Container:**
   ```bash
   docker compose up -d --build
   ```

2. **Test Container Response:**
   ```bash
   curl -i http://127.0.0.1:8080/Microsoft-Server-ActiveSync
   # Expected: HTTP 401 Unauthorized (Z-Push is working and asking for authentication)
   ```

3. **Configure Plesk Wildcard Proxy:**
   Copy `/usr/local/psa/admin/conf/templates/default/domain/nginxDomainVirtualHost.php` to `/usr/local/psa/admin/conf/templates/custom/domain/nginxDomainVirtualHost.php`.
   Add the following block inside the `server` context:

   ```nginx
   location /Microsoft-Server-ActiveSync {
       proxy_pass http://127.0.0.1:8080/Microsoft-Server-ActiveSync;
       proxy_set_header Host $http_host;
       proxy_set_header X-Real-IP $remote_addr;
       proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
       proxy_set_header X-Forwarded-Proto $scheme;
       proxy_set_header Authorization $http_authorization;
       proxy_pass_header Authorization;
       proxy_connect_timeout 3600s;
       proxy_read_timeout 3600s;
       proxy_send_timeout 3600s;
       proxy_buffering off;
       client_max_body_size 20M;
   }
   ```

4. **Rebuild Plesk Web Server Configuration:**
   ```bash
   plesk repair web -y
   systemctl reload nginx
   ```

---

## Testing & Verification

Navigate to `https://any-of-your-domains.com/Microsoft-Server-ActiveSync` in a browser or test with an Exchange/ActiveSync client (iOS Mail, Android Outlook, Windows Mail).

You should be prompted for basic authentication username (`user@domain.com`) and password.

---

## Persistent State

`docker-compose.yml` bind-mounts Z-Push's device state and logs to host directories next to the compose file, so they survive `docker compose up -d --build`, container recreation, and host reboots:

| Host path | Container path | Contents |
| :--- | :--- | :--- |
| `./data/state` | `/var/lib/z-push` | Per-device sync state (`STATE_DIR`) - deleting a device's subfolder here forces it to do a full resync |
| `./data/log` | `/var/log/z-push` | Z-Push application logs and PHP error log (`php-error.log`) |

Both directories are created automatically on first `docker compose up`, owned by `www-data` inside the container. They're excluded from git via `.gitignore`.

---

## Environment Variables

| Variable | Default | Description |
| :--- | :--- | :--- |
| `TIMEZONE` | `UTC` | PHP Timezone (e.g. `Europe/Istanbul`) |
| `IMAP_SERVER` | `host.docker.internal` | Mail server IP/hostname running Dovecot |
| `IMAP_PORT` | `143` | Dovecot IMAP port |
| `IMAP_OPTIONS` | `/notls` | IMAP SSL/TLS connection options |
| `SMTP_SERVER` | `host.docker.internal` | SMTP server IP/hostname running Postfix |
| `SMTP_PORT` | `25` | Postfix SMTP port |
| `SMTP_AUTH` | `true` | Whether to authenticate with Postfix before sending (set `false` to disable) |
| `SMTP_AUTH_METHOD` | `PLAIN` | SMTP AUTH mechanism to force - avoids Net_SMTP auto-negotiating DIGEST-MD5, whose client implementation sends a blank username to Postfix |
| `SMTP_HELO` | `localhost` | Hostname sent in the SMTP EHLO/HELO greeting |
| `USE_FULLEMAIL_FOR_LOGIN` | `true` | Required for Plesk multi-domain logins |
| `LOGLEVEL` | `LOGLEVEL_INFO` | Logging level (`LOGLEVEL_DEBUG` for troubleshooting) |
