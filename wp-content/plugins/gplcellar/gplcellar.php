<?php
/**
 * Plugin Name: GPL Cellar
 * Plugin URI: https://www.gplcellar.com
 * Description: WordPress Plugin & Theme Manager - Access thousands of WordPress plugins and themes through GPL Cellar
 * Version: 3.6.1
 * Tested up to: 6.5.2
 * Author: GPL Cellar
 * Author URI: https://www.gplcellar.com
 */

defined( 'ABSPATH' ) or die( 'Direct script access diallowed.' );

/**
 * Set your plugin globals here:
 */
define('GPLCELLAR_BETA', false);
define('GPLCELLAR_VERSION', '3.6.1');
define('GPLCELLAR_URL', 'https://www.gplcellar.com');
define('GPLCELLAR_BASE_FILE', plugin_basename(__FILE__));
define('GPLCELLAR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GPLCELLAR_PLUGIN_DIR', __FILE__);
define('GPLCELLAR_PLUGIN_BASEDIR', plugin_dir_path( __FILE__ ) );

require_once 'vendor/autoload.php';
require_once 'vendor/puc/plugin-update-checker.php';

/**
 * Use files from ./includes directory.
 */
GPLCellar\Registers::init();
GPLCellar\App::init();
GPLCellar\Configs::init();
GPLCellar\Ajax::init();
GPLCellar\Updater::init();
GPLCellar\Catalog::init();
GPLCellar\Database::init();
GPLCellar\License::init();
GPLCellar\WhiteLabel::init();
