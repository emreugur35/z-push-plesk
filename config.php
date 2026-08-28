<?php
/**********************************************************************************
 * Z-Push Main Configuration File for Plesk Docker
 **********************************************************************************/

// Helper function to get environment variables with defaults
function get_env_val($name, $default) {
    $val = getenv($name);
    return ($val !== false && $val !== '') ? $val : $default;
}

function get_env_bool($name, $default) {
    $val = getenv($name);
    if ($val === false || $val === '') {
        return $default;
    }
    return in_array(strtolower($val), array('true', '1', 'on', 'yes'), true);
}

// Timezone
define('TIMEZONE', get_env_val('TIMEZONE', 'UTC'));

// Base Settings
define('STATE_DIR', get_env_val('STATE_DIR', '/var/lib/z-push/'));
define('LOGFILEDIR', get_env_val('LOGFILEDIR', '/var/log/z-push/'));

// Logging level: LOGLEVEL_OFF, LOGLEVEL_FATAL, LOGLEVEL_ERROR, LOGLEVEL_WARN, LOGLEVEL_INFO, LOGLEVEL_DEBUG, LOGLEVEL_WBXML
$loglevel_str = strtoupper(get_env_val('LOGLEVEL', 'LOGLEVEL_INFO'));
if (defined($loglevel_str)) {
    define('LOGLEVEL', constant($loglevel_str));
} else {
    define('LOGLEVEL', LOGLEVEL_INFO);
}
define('LOGAUTHFAIL', true);

// ActiveSync Protocol Version
define('SYNC_MAX_ITEMS', 50);

// Backend Provider ('BackendIMAP', 'BackendCombined', etc.)
define('BACKEND_PROVIDER', get_env_val('BACKEND_PROVIDER', 'BackendIMAP'));

// Plesk Specific: Full email login (user@domain.com) MUST be enabled for multi-domain support
define('USE_FULLEMAIL_FOR_LOGIN', get_env_bool('USE_FULLEMAIL_FOR_LOGIN', true));

// Security & Provisioning
define('PROVISIONING', get_env_bool('PROVISIONING', true));
define('LOOSE_PROVISIONING', get_env_bool('LOOSE_PROVISIONING', true));

// Default Ping Interval (seconds)
define('PING_INTERVAL', 60);

// Set File permissions mode
define('FILE_PERMISSION_MODE', 0600);
define('DIR_PERMISSION_MODE', 0700);

// Global Additional Settings
$special_of_type = array();

// Ensure required paths exist
if (!is_dir(STATE_DIR)) {
    @mkdir(STATE_DIR, 0755, true);
}
if (!is_dir(LOGFILEDIR)) {
    @mkdir(LOGFILEDIR, 0755, true);
}
