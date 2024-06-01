<?php
/**
 * Used to load gplcellar plugin settings upon activation.
 */
namespace GPLCellar;

use WP_Filesystem_Direct;

class Configs
{
    private static $config_file = "";
    public static $configs = [
        'API_KEY' => '',
        'PRODUCT_ID' => '',
        'AGENCY_NAME' => '',
        'AGENCY_URL' => '',
        'PLUGIN_NAME' => '',
        'PLUGIN_DESCRIPTION' => '',
        'PLUGIN_LOGO_URL' => '',
        'PLUGIN_ICON_URL' => '',
        'WHITE_LABEL_ENABLED' => false,
    ];

    public static function init()
    {
        self::$config_file = self::locate_config_file();
        self::read_config();
    }

    /**
     * On plugin activation.
     * 
     * @since 3.5.0
     */
    public static function activation()
    {
        // copy config file from plugin dir (if exists) to backup dir
        $source = GPLCELLAR_PLUGIN_BASEDIR . "config.php";
        $destination = App::get_tmp_directory() . "config.php";

        if( file_exists($source) ) {
            if( !class_exists('WP_Filesystem_Direct', false)) {
                require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
                require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
            }

            $filesystem = new WP_Filesystem_Direct( false );

            $success = $filesystem->copy( $source, $destination, $overwrite=true, $mode=0644 );

            if( !$success ) {
                Log::write('GPLCellar failed to copy config.php file from ' . $source . ' to ' . $destination);
            }

            $success = $filesystem->delete( $source );

            if( !$success ) {
                Log::write('GPLCellar failed to remove config.php file from ' . $destination);
            }

            self::$config_file = $destination;
        }
    }

    public static function read_config()
    {
        if (self::config_exists()) {
            try {
                $config = include(self::$config_file);
                self::$configs = array_merge(self::$configs, $config);
            } catch (ErrorException $e) {
                Log::write("ERROR trying to read config file," . sefl::$config_file);
                Log::write($e->getMessage());
            }
        }

        return self::$configs;
    }

    public static function config_exists()
    {
        if( file_exists(self::$config_file) ) {
            return true;
        }
        return false;
    }

    private static function locate_config_file()
    {
        // @since 3.5.0 config file can live in plugin directory
        if( file_exists(GPLCELLAR_PLUGIN_BASEDIR . "config.php") ) {
            return GPLCELLAR_PLUGIN_BASEDIR. "config.php";
        }
        elseif ( file_exists(App::get_tmp_directory() . "config.php") ) {
            return App::get_tmp_directory() . "config.php";
        }

        return '';
    }
}
