<?php
/**********************************************************************************
 * Z-Push IMAP Backend Configuration for Plesk Docker
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

// Mailbox folder mapping
define('IMAP_FOLDER_INBOX', 'INBOX');
define('IMAP_FOLDER_SENT', imap_get_env('IMAP_FOLDER_SENT', 'Sent'));
define('IMAP_FOLDER_DRAFT', imap_get_env('IMAP_FOLDER_DRAFT', 'Drafts'));
define('IMAP_FOLDER_TRASH', imap_get_env('IMAP_FOLDER_TRASH', 'Trash'));
define('IMAP_FOLDER_SPAM', imap_get_env('IMAP_FOLDER_SPAM', 'Junk'));

// Mail sending configuration (SMTP via Plesk Postfix)
define('IMAP_SMTP_METHOD', 'smtp');
$global_imap_smtp_params = array(
    'host' => imap_get_env('SMTP_SERVER', 'host.docker.internal'),
    'port' => (int) imap_get_env('SMTP_PORT', 25),
    'auth' => imap_get_env_bool('SMTP_AUTH', true),
    'helo' => imap_get_env('SMTP_HELO', 'localhost')
);

// Subscribe to folders automatically
define('IMAP_AUTOREGISTER_FOLDER', true);
define('IMAP_DEFAULT_FULL_ADDRESS', true);

// Character set fallback
define('IMAP_DEFAULT_CHARSET', 'UTF-8');
