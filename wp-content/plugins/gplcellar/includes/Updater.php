<?php
/**
 * Class file used to load our plugins app.js file created by React.
 */
namespace GPLCellar;

use Puc_v4_Factory;

class Updater
{
    public static function init()
    {
        $info_file = 'https://files.gplcellar.com/gplcellar/info.v3.json';

        if (defined('GPLCELLAR_BETA') && GPLCELLAR_BETA) {
            $info_file = 'https://files.gplcellar.com/gplcellar/beta/info.json';
        }
        
        $gplcellarUpdateChecker = \Puc_v4_Factory::buildUpdateChecker(
            $info_file,
            GPLCELLAR_PLUGIN_DIR,
            'gplcellar',
            );

        add_action('upgrader_process_complete', [__CLASS__, 'gplcellar_after_update'], 10, 2);
        add_action('plugins_loaded', [__CLASS__, 'update_version_check']);
        add_filter('plugin_row_meta', [__CLASS__, 'hide_view_details'], 10, 4);
    }

    public static function uninstall()
    {
        delete_option('gplcellar_version');
    }

    public static function gplcellar_after_update($upgrader_object, $options)
    {
        if ($options['action'] == 'update' && $options['type'] === 'plugin') {
            delete_transient('gplcellar_plugin_upgrader');
            delete_transient('gpldb_unique_categories');
        }
    }

    public static function update_version_check()
    {
        if (get_site_option('gplcellar_version') != GPLCELLAR_VERSION) {
            $product_id = License::instance()->get_product_id();
            $api_key = License::instance()->get_license();

            if (License::instance()->account_status() == 'active' && $product_id != '' && $api_key != '') {
                License::instance()->reactivate();

                update_option("gplcellar_version", GPLCELLAR_VERSION);
            }
        }
    }

    /**
     * Hides the View Details for our plugin on WordPress Dashboard -> Plugins page.
     */
    public static function hide_view_details($plugin_meta, $plugin_file, $plugin_data, $status)
    {
        if (isset($plugin_data['slug']) && $plugin_data['slug'] == 'gplcellar') {
            unset($plugin_meta[2]);
        }
        return $plugin_meta;
    }
}
