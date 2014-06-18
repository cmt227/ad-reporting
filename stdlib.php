<?php
/**
 * Grooveshark Standard Library
 * Defines global system constants and frequently-used
 * functions.
 *
 * @author Skyler Slade <skyler.slade@escapemg.com>
 * @copyright 2008 Escape Media Group Inc.
 * @package lib
 */
date_default_timezone_set('America/New_York');
ini_set('memory_limit', '64M');
ini_set('error_log', '/var/log/php/reportads_log');
ini_set('display_errors', 'On');
ini_set('log_errors_max_len', 0);
ini_set('session.cookie_domain', "reportads.dev.grooveshark.com");
error_reporting(E_ALL);

define('APP_PATH', dirname(__FILE__) . '/');
define('APP_LIB_PATH', '/opt/www/sites/james/weblib/');
define('DSN', 'gshark_fs:remote_user:db1.in.escapemg.com:gshark_db');

// Smarty (GSmarty) paths
if (!defined('PATH_SMARTY')) {
    define('PATH_SMARTY', APP_LIB_PATH . 'Smarty/');
    define('PATH_SMARTY_LIB', PATH_SMARTY . 'libs/');
    define('PATH_SMARTY_CFG', PATH_SMARTY . 'configs/');
    define('PATH_SMARTY_TPL', APP_LIB_PATH . 'Tpl/');
    define('PATH_SMARTY_TPLC', APP_PATH . 'cache/smarty_templates_c/');
    define('PATH_SMARTY_CACHE', APP_PATH . 'cache/smarty_cache/');
}

define('MEMCACHE_DISABLED', false);
/**
 * Automatically Load Classes not Found in Scope
 * @param string $class
 * @void
 */
function __autoload($class)
{
    $path = str_replace('_', DIRECTORY_SEPARATOR, $class);
    $path = APP_LIB_PATH . $path . '.php';
    require_once($path);
}

if (!defined('CONF_PATH') && !defined('IMPORTING')) {
    define('CONF_PATH', '/opt/www/sites/james/conf/staging/');
    include CONF_PATH . 'conf.php';
    define('WEB_VERSION', MemcacheVersions::getCowbellVersion());
}
?>
