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
| [`Dockerfile`](Dockerfile) | PHP 8.2 + Apache image with Z-Push 2.7.6 and required extensions |
| [`docker-compose.yml`](docker-compose.yml) | Docker Compose service definition on port `127.0.0.1:8080` |
| [`config.php`](config.php) | Z-Push main config with dynamic environment variable support |
| [`imap.conf.php`](imap.conf.php) | Z-Push IMAP backend config for Plesk mail authentication |
| [`apache-zpush.conf`](apache-zpush.conf) | Apache virtualhost configuration inside the container |
| [`plesk-zpush-wildcard.conf`](plesk-zpush-wildcard.conf) | Nginx location directives for Plesk proxying |
| [`deploy-plesk-wildcard.sh`](deploy-plesk-wildcard.sh) | One-touch automated deployment script for Plesk servers |
| [`backup-plesk-templates.sh`](backup-plesk-templates.sh) | Creates timestamped safety backups of Plesk configuration templates (with restore option) |

---

## Quick Start / Deployment Instructions

### 1. Clone or Upload to Plesk Host
Upload this repository to `/opt/z-push-docker` or any directory on your Plesk server:

```bash
git clone https://github.com/emreugur35/z-push-plesk.git /opt/z-push-docker
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
| `IMAP_FOLDER_PREFIX` | `INBOX` | Namespace prefix of the special folders (no trailing delimiter). Plesk's Dovecot stores them as `INBOX.Sent`, `INBOX.Drafts`, `INBOX.Trash`; without this, sent mail isn't saved to Sent ("The email could not be saved to Sent Items folder"). Set to empty for servers with top-level `Sent`/`Drafts`/`Trash` |
| `IMAP_FOLDER_PREFIX_IN_INBOX` | `false` | Also prefix the Inbox itself (only for servers where Inbox is e.g. `INBOX.INBOX`) |
| `IMAP_FOLDER_INBOX` | `INBOX` | Inbox folder name |
| `IMAP_FOLDER_SENT` | `Sent` | Sent folder name, without the prefix (e.g. `Sent Items`) - where sent mail is saved |
| `IMAP_FOLDER_DRAFT` | `Drafts` | Drafts folder name, without the prefix |
| `IMAP_FOLDER_TRASH` | `Trash` | Trash folder name, without the prefix (e.g. `Deleted Items`) |
| `IMAP_FOLDER_SPAM` | `Junk` | Junk/spam folder name, without the prefix (e.g. `Spam`) |
| `IMAP_FOLDER_ARCHIVE` | `Archive` | Archive folder name, without the prefix |
| `SMTP_SERVER` | `host.docker.internal` | SMTP server IP/hostname running Postfix |
| `SMTP_PORT` | `587` | Postfix submission port (STARTTLS) - see [SMTP Submission Port Configuration](#smtp-submission-port-587-configuration-on-the-plesk-mail-server) below |
| `SMTP_AUTH` | `true` | Whether to authenticate with Postfix before sending (set `false` to disable) |
| `SMTP_AUTH_METHOD` | `PLAIN` | SMTP AUTH mechanism to force - avoids Net_SMTP auto-negotiating DIGEST-MD5, whose client implementation sends a blank username to Postfix |
| `SMTP_HELO` | `localhost` | Hostname sent in the SMTP EHLO/HELO greeting |
| `USE_FULLEMAIL_FOR_LOGIN` | `true` | Required for Plesk multi-domain logins |
| `LOGLEVEL` | `LOGLEVEL_INFO` | Logging level (`LOGLEVEL_DEBUG` for troubleshooting) |

---

## SMTP Submission Port (587) Configuration on the Plesk Mail Server

Z-Push sends outgoing mail (Send Message) through Postfix's `submission` service on port `587`, authenticating with the same credentials the user logged into IMAP with (`SMTP_AUTH_METHOD=PLAIN`, forced - see above). Because the container reaches Postfix as `host.docker.internal` rather than the mail server's real hostname, TLS certificate/hostname verification is already disabled on the client side in `imap.conf.php`. For this local, same-host connection to authenticate reliably, Postfix's `submission` service also needs its TLS requirement relaxed from mandatory to opportunistic.

On the Plesk mail server (the Docker host), edit Postfix's master process config:

```bash
nano /etc/postfix/master.cf
```

Find (or add) the `submission` service block and set it to:

```text
submission inet n       -       n       -       -       smtpd
  -o smtpd_enforce_tls=yes
  -o smtpd_tls_security_level=may
  -o smtpd_sasl_auth_enable=yes
```

Then reload Postfix for the change to take effect:

```bash
postfix reload
```

**Note:** `smtpd_tls_security_level=may` makes TLS opportunistic rather than mandatory on the submission port. This is an acceptable trade-off here because the Z-Push container only ever reaches Postfix over the Docker host-internal bridge (`host.docker.internal`), never over the public internet. If your server's `submission` port is also exposed to external/internet-facing clients, don't apply this relaxed setting globally - scope it to the local connection only, or keep TLS mandatory (`encrypt`) for those clients.
