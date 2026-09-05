<?php
/**********************************************************************************
 * Z-Push Main Configuration File for Plesk Docker
 *
 * This is based on the full upstream Z-Push config.php (Z-Hub/Z-Push 2.7.6) so that
 * every constant the core code expects is always defined. Only the settings this
 * Plesk/Docker image is meant to be tuned by are overridable via environment
 * variables (see get_env_val()/get_env_bool() below); everything else keeps
 * upstream's documented default.
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

/**********************************************************************************
 *  Default settings
 */
// Defines the default time zone, change e.g. to "Europe/London" if necessary
define('TIMEZONE', get_env_val('TIMEZONE', 'UTC'));

// Defines the base path on the server
define('BASE_PATH', dirname($_SERVER['SCRIPT_FILENAME']). '/');

// Try to set unlimited timeout
define('SCRIPT_TIMEOUT', 0);

// Use a custom header to determinate the remote IP of a client.
// By default, the server provided REMOTE_ADDR is used.
// set to false to disable this behaviour.
define('USE_CUSTOM_REMOTE_IP_HEADER', false);

// When using client certificates, we can check if the login sent matches the owner of the certificate.
define('CERTIFICATE_OWNER_PARAMETER', 'SSL_CLIENT_S_DN_CN');

// Plesk Specific: Full email login (user@domain.com) MUST be enabled for multi-domain support
define('USE_FULLEMAIL_FOR_LOGIN', get_env_bool('USE_FULLEMAIL_FOR_LOGIN', true));

/**********************************************************************************
 * StateMachine setting
 *
 * These StateMachines can be used:
 *   FILE  - FileStateMachine (default). Needs STATE_DIR set as well.
 *   SQL   - SqlStateMachine has own configuration file. STATE_DIR is ignored.
 */
define('STATE_MACHINE', 'FILE');
define('STATE_DIR', get_env_val('STATE_DIR', '/var/lib/z-push/'));

/**********************************************************************************
 *  IPC - InterProcessCommunication
 */
define('IPC_PROVIDER', '');

/**********************************************************************************
 *  Logging settings
 */
define('LOGBACKEND', 'filelog');

// Logging level: LOGLEVEL_OFF, LOGLEVEL_FATAL, LOGLEVEL_ERROR, LOGLEVEL_WARN, LOGLEVEL_INFO, LOGLEVEL_DEBUG, LOGLEVEL_WBXML
$loglevel_str = strtoupper(get_env_val('LOGLEVEL', 'LOGLEVEL_INFO'));
if (defined($loglevel_str)) {
    define('LOGLEVEL', constant($loglevel_str));
} else {
    define('LOGLEVEL', LOGLEVEL_INFO);
}
define('LOGAUTHFAIL', get_env_bool('LOGAUTHFAIL', true));

// To save e.g. WBXML data only for selected users, add the usernames to the array
define('LOGUSERLEVEL', LOGLEVEL_DEVICEID);
$specialLogUsers = array();

// Filelog settings
define('LOGFILEDIR', get_env_val('LOGFILEDIR', '/var/log/z-push/'));
define('LOGFILE', LOGFILEDIR . 'z-push.log');
define('LOGERRORFILE', LOGFILEDIR . 'z-push-error.log');

// Syslog settings (unused while LOGBACKEND is 'filelog', kept defined for completeness)
define('LOG_SYSLOG_HOST', false);
define('LOG_SYSLOG_PORT', 514);
define('LOG_SYSLOG_PROGRAM', 'z-push');
define('LOG_SYSLOG_FACILITY', LOG_LOCAL0);

/**********************************************************************************
 *  Mobile settings
 */
// Device Provisioning
define('PROVISIONING', get_env_bool('PROVISIONING', true));

// Allow older devices which don't support provisioning, but enforce policies where supported
define('LOOSE_PROVISIONING', get_env_bool('LOOSE_PROVISIONING', true));

// The file containing the policies' settings, relative to the z-push main directory
define('PROVISIONING_POLICYFILE', 'policies.ini');

// Default conflict preference: Server is overwritten (PIM wins) vs PIM is overwritten (Server wins, default)
define('SYNC_CONFLICT_DEFAULT', SYNC_CONFLICT_OVERWRITE_PIM);

// Global limitation of items to be synchronized (no limitation by default)
define('SYNC_FILTERTIME_MAX', SYNC_FILTERTYPE_ALL);

// Default Ping Interval (seconds)
define('PING_INTERVAL', get_env_val('PING_INTERVAL', 60));

// Fileas (save as) order for contacts in the webaccess/webapp/outlook
define('FILEAS_ORDER', SYNC_FILEAS_LASTFIRST);

// ActiveSync Protocol Version: maximum items to be synchronized per request
define('SYNC_MAX_ITEMS', get_env_val('SYNC_MAX_ITEMS', 50));

// Whether to unset properties which are not sent during Sync (default: false)
define('UNSET_UNDEFINED_PROPERTIES', false);

// Max contact photo size in bytes (default: 5 MB)
define('SYNC_CONTACTS_MAXPICTURESIZE', 5242880);

// Disabled by default: retrieving the list of all known devices/users needs care in multi-tenant setups
define('ALLOW_WEBSERVICE_USERS_ACCESS', false);

// Experimental: partial foldersync for users with many folders
define('USE_PARTIAL_FOLDERSYNC', false);

// No lower/higher bound on the ping lifetime by default
define('PING_LOWER_BOUND_LIFETIME', false);
define('PING_HIGHER_BOUND_LIFETIME', false);

// Device categories with longer response timeouts
define('SYNC_TIMEOUT_MEDIUM_DEVICETYPES', 'SAMSUNGGTI');
define('SYNC_TIMEOUT_LONG_DEVICETYPES', 'iPod, iPad, iPhone, WP, WindowsOutlook, WindowsMail');

// Seconds a device should wait when the service is unavailable ("Retry-After" header)
define('RETRY_AFTER_DELAY', 300);

/**********************************************************************************
 *  Backend settings
 */
// Backend Provider ('BackendIMAP', 'BackendCombined', etc.). Empty lets Z-Push autoload one.
define('BACKEND_PROVIDER', get_env_val('BACKEND_PROVIDER', 'BackendIMAP'));

/**********************************************************************************
 *  Search provider settings
 */
define('SEARCH_PROVIDER', '');
define('SEARCH_WAIT', 10);
define('SEARCH_MAXRESULTS', 10);

/**********************************************************************************
 *  Kopano Outlook Extension - Settings
 *  Only relevant when using BackendKopano; harmless defaults otherwise.
 */
define('KOE_CAPABILITY_GAB', true);
define('KOE_CAPABILITY_RECEIVEFLAGS', true);
define('KOE_CAPABILITY_SENDFLAGS', true);
define('KOE_CAPABILITY_OOF', true);
define('KOE_CAPABILITY_OOFTIMES', true);
define('KOE_CAPABILITY_NOTES', true);
define('KOE_CAPABILITY_SHAREDFOLDER', true);
define('KOE_CAPABILITY_SENDAS', true);
define('KOE_CAPABILITY_SECONDARYCONTACTS', true);
define('KOE_CAPABILITY_SIGNATURES', true);
define('KOE_CAPABILITY_RECEIPTS', true);
define('KOE_CAPABILITY_IMPERSONATE', true);
define('KOE_GAB_STORE', 'SYSTEM');
define('KOE_GAB_FOLDERID', '');
define('KOE_GAB_NAME', 'Z-Push-KOE-GAB');

/**********************************************************************************
 *  Synchronize additional folders to all mobiles - none by default
 */
$additionalFolders = array();

/**********************************************************************************
 *  Plesk / Docker specific settings
 */
// Set File permissions mode
define('FILE_PERMISSION_MODE', 0600);
define('DIR_PERMISSION_MODE', 0700);

// Ensure required paths exist
if (!is_dir(STATE_DIR)) {
    @mkdir(STATE_DIR, 0755, true);
}
if (!is_dir(LOGFILEDIR)) {
    @mkdir(LOGFILEDIR, 0755, true);
}
