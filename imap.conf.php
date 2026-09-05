<?php
/**********************************************************************************
 * Z-Push IMAP Backend Configuration for Plesk Docker
 *
 * Based on the full upstream backend/imap/config.php (Z-Hub/Z-Push 2.7.6) so every
 * constant BackendIMAP expects is always defined. Only the settings this Plesk/
 * Docker image is meant to be tuned by are overridable via environment variables;
 * everything else keeps upstream's documented default.
 **********************************************************************************/

function imap_get_env($name, $default) {
    $val = getenv($name);
    return ($val !== false && $val !== '') ? $val : $default;
}

function imap_get_env_bool($name, $default) {
    $val = getenv($name);
    if ($val === false || $val === '') {
        return $default;
    }
    return in_array(strtolower($val), array('true', '1', 'on', 'yes'), true);
}

// IMAP Server host (Plesk Dovecot host IP or host.docker.internal)
define('IMAP_SERVER', imap_get_env('IMAP_SERVER', 'host.docker.internal'));
define('IMAP_PORT', (int) imap_get_env('IMAP_PORT', 143));

// IMAP connection flags (/notls, /ssl, /novalidate-cert, etc.)
define('IMAP_OPTIONS', imap_get_env('IMAP_OPTIONS', '/notls'));

// Mark messages as read when moving to Trash (upstream default: false)
define('IMAP_AUTOSEEN_ON_DELETE', false);

// Folder names below are configured (with sensible defaults), so tell BackendIMAP
// not to refuse logon with "you didn't configure your IMAP folder names".
define('IMAP_FOLDER_CONFIGURED', true);

// Folder prefix is the common part in folder names - not used in the plain
// INBOX/Sent/Drafts/Trash layout this image defaults to.
define('IMAP_FOLDER_PREFIX', '');
define('IMAP_FOLDER_PREFIX_IN_INBOX', false);

// Mailbox folder mapping
define('IMAP_FOLDER_INBOX', 'INBOX');
define('IMAP_FOLDER_SENT', imap_get_env('IMAP_FOLDER_SENT', 'Sent'));
define('IMAP_FOLDER_DRAFT', imap_get_env('IMAP_FOLDER_DRAFT', 'Drafts'));
define('IMAP_FOLDER_TRASH', imap_get_env('IMAP_FOLDER_TRASH', 'Trash'));
define('IMAP_FOLDER_SPAM', imap_get_env('IMAP_FOLDER_SPAM', 'Junk'));
define('IMAP_FOLDER_ARCHIVE', 'Archive');

// forward messages inline (upstream default: true)
define('IMAP_INLINE_FORWARD', true);

// list of folders to exclude from sync, '|'-separated (none by default)
define('IMAP_EXCLUDED_FOLDERS', '');

// overwrite the "from" header - '' means use the message's own From header
define('IMAP_DEFAULTFROM', '');

// SQL/LDAP lookup settings for IMAP_DEFAULTFROM - unused defaults, kept so the
// constants exist if BackendIMAP ever reads them
define('IMAP_FROM_SQL_DSN', '');
define('IMAP_FROM_SQL_USER', '');
define('IMAP_FROM_SQL_PASSWORD', '');
define('IMAP_FROM_SQL_OPTIONS', serialize(array(PDO::ATTR_PERSISTENT => true)));
define('IMAP_FROM_SQL_QUERY', "select first_name, last_name, mail_address from users where mail_address = '#username@#domain'");
define('IMAP_FROM_SQL_FIELDS', serialize(array('first_name', 'last_name', 'mail_address')));
define('IMAP_FROM_SQL_EMAIL', '#mail_address');
define('IMAP_FROM_SQL_FROM', '#first_name #last_name <#mail_address>');
define('IMAP_FROM_SQL_FULLNAME', '#first_name #last_name');

define('IMAP_FROM_LDAP_SERVER_URI', 'ldap://127.0.0.1:389/');
define('IMAP_FROM_LDAP_USER', 'cn=zpush,ou=servers,dc=zpush,dc=org');
define('IMAP_FROM_LDAP_PASSWORD', 'password');
define('IMAP_FROM_LDAP_BASE', 'dc=zpush,dc=org');
define('IMAP_FROM_LDAP_QUERY', '(mail=#username@#domain)');
define('IMAP_FROM_LDAP_FIELDS', serialize(array('givenname', 'sn', 'mail')));
define('IMAP_FROM_LDAP_EMAIL', '#mail');
define('IMAP_FROM_LDAP_FROM', '#givenname #sn <#mail>');
define('IMAP_FROM_LDAP_FULLNAME', '#givenname #sn');

// Mail sending configuration (SMTP via Plesk Postfix).
// BackendIMAP reads `global $imap_smtp_params` directly (imap.php ~line 2627) -
// it must be this exact variable name, not a differently-named one, or these
// settings are silently ignored and mail gets sent with no SMTP params at all.
define('IMAP_SMTP_METHOD', 'smtp');
global $imap_smtp_params;
$imap_smtp_params = array(
    'host' => imap_get_env('SMTP_SERVER', 'host.docker.internal'),
    'port' => (int) imap_get_env('SMTP_PORT', 25),
    'auth' => imap_get_env_bool('SMTP_AUTH', true),
    'localhost' => imap_get_env('SMTP_HELO', 'localhost'),
    // Postfix advertises STARTTLS and (with Plesk's default smtpd_tls_auth_only=yes)
    // requires it before AUTH, so TLS can't just be turned off. But its certificate's
    // CN is the real server hostname (e.g. adsunucusu.com), not 'host.docker.internal' -
    // no cert will ever match that Docker-only alias, and this image never installs
    // ca-certificates, so the chain may not validate either. Since this connection
    // never leaves the Docker host (container -> host.docker.internal -> the same
    // machine's Postfix), skipping both checks is an acceptable trade-off here.
    //
    // IMPORTANT: Z-Push vendors its own Mail/smtp.php + Net/SMTP.php + Net/Socket.php
    // under backend/imap/ (not the standalone PEAR package). These classes read
    // verify_peer/verify_peer_name as TOP-LEVEL keys here, and Net/Socket.php's
    // enableCrypto() rebuilds the SSL context from exactly these values right before
    // the STARTTLS handshake - options nested under a 'socket_options' key only
    // apply to the initial plaintext connection and get overwritten before crypto
    // starts, so they must be set here directly or they're silently ignored.
    'verify_peer' => false,
    'verify_peer_name' => false,
);

// RFC 2822 requires \r\n; only change this if not using the smtp method above
define('MAIL_MIMEPART_CRLF', "\r\n");

// A file containing file mime types->extension mappings (guarded by file_exists()
// in BackendIMAP, but the constant itself must be defined or it fatals outright).
define('SYSTEM_MIME_TYPES_MAPPING', '/etc/mime.types');

// Use BackendCalDAV for Meetings - not configured in this image, so false
define('IMAP_MEETING_USE_CALDAV', false);

// Charset used for IMAP SEARCH
define('IMAP_SEARCH_CHARSET', 'UTF-8');
