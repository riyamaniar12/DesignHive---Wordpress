<?php

namespace GPLCellar;

class Plugin
{
    private static $plugin_update_info = array();

    public static function init()
    {
        if (is_admin()) {
            if (! function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            add_filter('site_transient_update_plugins', array( __CLASS__, 'modify_update_plugins'), 9999999);
            add_filter('site_transient_update_plugins', array( __CLASS__, 'disable_check_updater'), 9999998);

            if (Database::table_exists()) {
                self::plugins_update_hooks();
            }
        }
    }

    public static function plugins_update_hooks()
    {
        global $gpl_tmp_link;

        $installed_plugins = get_plugins();

        $installed_plugins_fp = Database::get_installed_gpl_plugins(array_keys($installed_plugins));

        if ($installed_plugins_fp) {
            foreach ($installed_plugins_fp as $plugin) {
                $slug = $plugin->filepath;
                if (isset($installed_plugins[$slug])) {
                    $gpl_tmp_link = '';
                    if (isset($plugin->permalink)) {
                        $gpl_tmp_link = $plugin->permalink;
                    }
                    $current_version = $installed_plugins[$slug]['Version'];
                    $remote_version = $plugin->version;
                    if (stristr($slug, '/') && version_compare($current_version, $remote_version, '<')) {
                        add_action('in_plugin_update_message-'.$slug, function ($data, $response) {
                            global $gpl_tmp_link;
                            $filter = '{"action":"gplcellar_get_items","pagesize":24,"pagenum":1,"filter":"installed","search":"","type":"plugin"}';
                            $url = '/admin.php?page=gplcellar-plugin-manager#'. rawurlencode($filter);
                            echo '<br> ';
                            printf(
                                '<a href="%s"><strong>Update with GPL Cellar</strong></a>',
                                admin_url(addslashes($url)),
                                $gpl_tmp_link
                          );
                        }, 9, 2);
                    }
                }
            }
        }
    }


    /**
     * @since 1.0.6
     */
    public static function modify_update_plugins($transient)
    {
        foreach (self::$plugin_update_info as $slug => $plugin) {
            $file_path = $plugin['file_path'];

            if (! isset($transient->response[ $file_path ])) {
                $transient->response[ $file_path ] = new \stdClass;
            }

            $transient->response[ $file_path ]->slug = $slug;
            $transient->response[ $file_path ]->plugin = $file_path;
            $transient->response[ $file_path ]->new_version = $plugin['version'];
            $transient->response[ $file_path ]->package = $plugin['source'];

            if (empty($transient->response[ $file_path ]->url) && ! empty($plugin['external_url'])) {
                $transient->response[ $file_path ]->url = $plugin['external_url'];
            }
        }

        return $transient;
    }


    /**
     * @since 1.0.6
     */
    public static function disable_check_updater($transient)
    {
        foreach (self::$plugin_update_info as $fp => $plugin) {
            $file_path = $plugin['file_path'];

            if (isset($transient->response[$file_path])) {
                unset($transient->response[$file_path]);
            }
        }

        return $transient;
    }


    public static function install($product_id, $zip)
    {
        $payload = 'Uncaught condition';

        $upgraded = false;
        $plugin = Database::get_item($product_id);

        if ($plugin) {
            // Plugin::do_plugin_install($plugin, $zip);
            WP_Filesystem();
            $result = unzip_file($zip, WP_PLUGIN_DIR);

            if ($result) {
                $payload = true;
            } else {
                $payload = 'Could not install';
            }
        } else {
            $payload = 'Could not find item';
        }

        unlink($zip);
        return $payload;
    }

    public static function update($product_id, $filepath, $zip)
    {
        $short_slug = explode('/', $filepath);
        $short_slug = $short_slug[0];

        $current_version = self::get_installed_version($filepath);

        $plugin_active = is_plugin_active($short_slug);

        deactivate_plugins($short_slug);

        WP_Filesystem();
        $result = unzip_file($zip, WP_PLUGIN_DIR);

        if ($plugin_active) {
            activate_plugins($short_slug);
        }

        unlink($zip);

        return $result;
    }

    /**
     * @NOTE: This was giving me problems when using to update plugins.
     * @deprecated 3.0.5 use unzip_file()
     * @since 3.0.5
     *
     * Internal method for installing a WordPress plugin.
     *
     * @param string $plugin  - Our plugin database object.
     * @param string $source  - URI to the latest plugin zip file.
     */
    public static function do_plugin_install($plugin, $source)
    {
        $slug = explode('/', $plugin->filepath);
        $slug = $slug[0];

        // $slug = self::sanitize_key(urldecode($slug));
        $slug = self::sanitize_key($slug);

        $item_type = 'plugin';

        $version = $plugin->version;
        $item_name = $plugin->item_name;
        $item_filepath = $plugin->filepath;

        $install_type = 'update';

        if (! class_exists('Plugin_Upgrader', false)) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        if (! class_exists('Plugin_Upgrader_Skin', false)) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';
        }

        $url = wp_nonce_url(
            add_query_arg(
                array(
                    'plugin' => urlencode($slug),
                    'gplcellar-' . $install_type => $install_type . '-plugin',
                ),
                ''
            ),
            'gplcellar-' . $install_type,
            'gplcellar-nonce'
        );

        $method = '';

        if (false === ($creds = request_filesystem_credentials(esc_url_raw($url), $method, false, false, array()))) {
            return true;
        }

        if (! WP_Filesystem($creds)) {
            request_filesystem_credentials(esc_url_raw($url), $method, true, false, array()); // Setup WP_Filesystem.
            return true;
        }

        $extra         = array();
        $extra['slug'] = $slug;
        $api =  null;
        $skin_arg_type = ($install_type == 'restore' ? 'bundled' : 'web');

        $url = add_query_arg(
            array(
                'action' => $install_type . '-plugin',
                'plugin' => urlencode($slug),
            ),
            'update.php'
        );
        $skin_args = array(
            'type'   => $skin_arg_type,
            'title'  => $item_name,
            'url'    => esc_url_raw($url),
            'nonce'  => $install_type . '-plugin_' . $slug,
            'plugin' => $item_filepath,
            'api'    => $api,
            'extra'  => $extra,
        );

        $ajax_skin =  new \WP_Ajax_Upgrader_Skin($skin_args);

        $upgrader = new \Plugin_Upgrader($ajax_skin);

        $to_inject  = array();
        $to_inject[ $item_filepath ]['file_path'] = $item_filepath;
        $to_inject[ $item_filepath ]['source'] = $source;
        $to_inject[ $item_filepath ]['version'] = $version;

        self::$plugin_update_info = $to_inject;
        self::inject_update_info($to_inject);

        $upgraded = $upgrader->upgrade($item_filepath);

        $errors = $ajax_skin->get_errors();

        wp_clean_plugins_cache(true);

        return $errors;
    }

    private static function sanitize_key($key)
    {
        $raw_key = $key;
        $key     = preg_replace('`[^A-Za-z0-9_-]`', '', $key);

        return apply_filters('gplcellar_sanitize_key', $key, $raw_key);
    }

    public static function is_plugin_installed($slug)
    {
        $installed_plugins = get_plugins();

        return (! empty($installed_plugins[ $slug ]));
    }


    public static function get_installed_version($slug)
    {
        $installed_plugins = get_plugins();

        if (! empty($installed_plugins[ $slug ])) {
            return $installed_plugins[$slug ]['Version'];
        }

        return '';
    }

    public static function activate_plugin($slug)
    {
        $activate = activate_plugin($slug);
        if (is_wp_error($activate)) {
            return false;
        } else {
            return true;
        }
    }

    protected static function get_plugins_api($slug)
    {
        static $api = array();
        if (! isset($api[ $slug ])) {
            if (! function_exists('plugins_api')) {
                require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            }
            $response = plugins_api('plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ));
            $api[ $slug ] = false;
            if (is_wp_error($response)) {
                wp_die(esc_html('Could not get plugin API info.'));
            } else {
                $api[ $slug ] = $response;
            }
        }
        return $api[ $slug ];
    }

    /**
     * Inject information into the 'update_plugins' site transient as WP checks that before running an update.
     *
     * @since 1.0.5
     *
     * @param array $plugins The plugin information for the plugins which are to be updated.
     */
    public static function inject_update_info($plugins)
    {
        $repo_updates = get_site_transient('update_plugins');

        if (! is_object($repo_updates)) {
            $repo_updates = new \stdClass;
        }

        foreach ($plugins as $slug => $plugin) {
            $file_path = $plugin['file_path'];

            if (empty($repo_updates->response[ $file_path ])) {
                $repo_updates->response[ $file_path ] = new \stdClass;
            }

            // We only really need to set package, but let's do all we can in case WP changes something.
            $repo_updates->response[ $file_path ]->slug        = $slug;
            $repo_updates->response[ $file_path ]->plugin      = $file_path;
            $repo_updates->response[ $file_path ]->new_version = $plugin['version'];
            $repo_updates->response[ $file_path ]->package     = $plugin['source'];
            if (empty($repo_updates->response[ $file_path ]->url) && ! empty($plugin['external_url'])) {
                $repo_updates->response[ $file_path ]->url = $plugin['external_url'];
            }
        }

        set_site_transient('update_plugins', $repo_updates);
    }

    public static function is_active($slug)
    {
        // WP's method
        return is_plugin_active($slug);
    }
}
