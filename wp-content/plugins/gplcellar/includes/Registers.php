<?php

/**
 * Class file used to register plugin hooks. Used for plugin activation,
 * deactivating, and deleting/uninstalling.
 */
namespace GPLCellar;

class Registers
{
    public static function init()
    {
        register_activation_hook(GPLCELLAR_BASE_FILE, [ __CLASS__, 'on_activation' ]);
        register_deactivation_hook(GPLCELLAR_BASE_FILE, [ __CLASS__, 'on_deactivate' ]);
        register_uninstall_hook(GPLCELLAR_BASE_FILE, [ __CLASS__, 'on_uninstall' ]);
    }

    public static function on_activation()
    {
        Configs::activation();
        Catalog::schedule_update();

        License::instance()->activation();
        License::instance()->auto_activate();

        Database::create();

        Catalog::get_catalog(true);
    }

    public static function on_deactivate()
    {
        Catalog::clear_schedule_update();
        // @since 3.2.4 - deactivate license
        License::instance()->deactivate();
    }

    public static function on_uninstall()
    {
        delete_option('gplcellar_themes');
        delete_option('gplcellar_plugins');
        delete_option('gplcellar_plugin_manager_product_id');

        License::instance()->uninstall();
        Catalog::uninstall();
        Database::uninstall();
        Updater::uninstall();
    }

}
