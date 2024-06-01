<?php

/**
 * Handles white label feature for our plugin.
 */
namespace GPLCellar;

class WhiteLabel
{
    public static function init()
    {
        register_activation_hook(GPLCELLAR_BASE_FILE, [ __CLASS__, 'on_activation' ]);
        register_deactivation_hook(GPLCELLAR_BASE_FILE, [ __CLASS__, 'on_deactivate' ]);
        register_uninstall_hook(GPLCELLAR_BASE_FILE, [ __CLASS__, 'on_uninstall' ]);

        add_filter( 'all_plugins', [__CLASS__, 'plugins_page']);

        // @since 3.4.0 - use Config settings to activate WhiteLabel
        if (Configs::config_exists()) {
          $config = Configs::read_config();
          self::save_options([
            'agency_name' => $config['AGENCY_NAME'],
            'agency_url' => $config['AGENCY_URL'],
            'plugin_name' => $config['PLUGIN_NAME'],
            'plugin_logo_url' => $config['PLUGIN_LOGO_URL'],
            'plugin_icon_url' => $config['PLUGIN_ICON_URL'],
            'plugin_description' => $config['PLUGIN_DESCRIPTION'],
            'enabled' => filter_var( $config['WHITE_LABEL_ENABLED'], FILTER_VALIDATE_BOOLEAN ),
          ]);
        }
    }

    public static function on_activation()
    {
    }

    public static function on_deactivate()
    {
        // @since 3.4.0 - deactivating the plugin no longer disables White label
        // update_option('gplcellar_wl_enabled', false);
    }

    public static function on_uninstall()
    {
        delete_option('gplcellar_wl_agency_name');
        delete_option('gplcellar_wl_agency_url');
        delete_option('gplcellar_wl_plugin_name');
        delete_option('gplcellar_wl_plugin_logo_url');
        delete_option('gplcellar_wl_plugin_icon_url');
        delete_option('gplcellar_wl_plugin_description');
        delete_option('gplcellar_wl_enabled');
    }

    public static function save_options($options)
    {
  			update_option('gplcellar_wl_agency_name', self::sanitize($options['agency_name']));
  			update_option('gplcellar_wl_agency_url', self::sanitize($options['agency_url']));
  			update_option('gplcellar_wl_plugin_name', self::sanitize($options['plugin_name']));
  			update_option('gplcellar_wl_plugin_logo_url', self::sanitize($options['plugin_logo_url']));
  			update_option('gplcellar_wl_plugin_icon_url', self::sanitize($options['plugin_icon_url']));
  			update_option('gplcellar_wl_plugin_description', self::sanitize($options['plugin_description']));
  			update_option('gplcellar_wl_enabled', filter_var( $options['enabled'], FILTER_VALIDATE_BOOLEAN ));

        return true;
    }

    public static function get_options()
    {
        return [
            'agency_name' => self::get_agency_name(),
            'agency_url' => self::get_agency_url(),
            'plugin_name' => self::get_plugin_name(),
            'plugin_logo_url' => self::get_plugin_logo_url(),
            'plugin_icon_url' => self::get_plugin_icon_url(),
            'plugin_description' => self::get_plugin_description(),
            'enabled' => self::is_enabled(),
        ];
    }

    public static function is_enabled()
    {
        return get_option('gplcellar_wl_enabled', false);
    }

    public static function get_agency_name()
    {
        return htmlspecialchars_decode(get_option('gplcellar_wl_agency_name', ''));
    }

    public static function get_agency_url()
    {
        return htmlspecialchars_decode(get_option('gplcellar_wl_agency_url', ''));
    }

    public static function get_plugin_name()
    {
        return htmlspecialchars_decode(get_option('gplcellar_wl_plugin_name', ''));
    }

    public static function get_plugin_logo_url()
    {
        return htmlspecialchars_decode(get_option('gplcellar_wl_plugin_logo_url', ''));
    }

    public static function get_plugin_icon_url()
    {
        return htmlspecialchars_decode(get_option('gplcellar_wl_plugin_icon_url', ''));
    }

    public static function get_plugin_description()
    {
        return htmlspecialchars_decode(get_option('gplcellar_wl_plugin_description', ''));
    }

    public static function sanitize($input)
    {
        return stripslashes_deep($input);
    }

    /**
     * Methods that override WordPress backend calls.
     */
    public static function get_wp_menu_icon($default='')
    {
        if (self::is_enabled()) {
          return self::get_plugin_icon_url();
        } else {
          return $default;
        }
    }

    public static function get_wp_menu_name($default='')
    {
        if (self::is_enabled()) {
          return self::get_plugin_name();
        } else {
          return $default;
        }
    }

    public static function get_wp_plugin_description($default='')
    {
        if (self::is_enabled()) {
          return self::get_plugin_description();
        } else {
          return $default;
        }
    }

    public static function get_wp_plugin_author($default='')
    {
        if (self::is_enabled()) {
          return self::get_agency_name();
        } else {
          return $default;
        }
    }

    public static function get_wp_plugin_url($default='')
    {
        if (self::is_enabled()) {
          return self::get_agency_url();
        } else {
          return $default;
        }
    }


    /**
     * Override the plugin meta data on the WordPress plugins page.
     */

    public static function plugins_page( $plugins )
    {
        if (self::is_enabled()) {
          $key = GPLCELLAR_BASE_FILE;

      		$plugins[ $key ]['Name'] = self::get_wp_menu_name();
      		$plugins[ $key ]['Description'] = self::get_wp_plugin_description();

      		$plugins[ $key ]['Author']     = self::get_wp_plugin_author();
      		$plugins[ $key ]['AuthorName'] = self::get_wp_plugin_author();

      		$plugins[ $key ]['AuthorURI'] = self::get_wp_plugin_url();
      		$plugins[ $key ]['PluginURI'] = self::get_wp_plugin_url();
        }

    	return $plugins;
    }
}
