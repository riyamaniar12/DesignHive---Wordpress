<?php
/**
 * Class file used to load our plugins app.js file created by React.
 */
namespace GPLCellar;

class App
{
    public static $menu_icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PCEtLSBHZW5lcmF0b3I6IEdyYXZpdC5pbyAtLT48c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHN0eWxlPSJpc29sYXRpb246aXNvbGF0ZSIgdmlld0JveD0iMCAwIDI2My4zNjYgMjUxLjUyIiB3aWR0aD0iMjYzLjM2NnB0IiBoZWlnaHQ9IjI1MS41MnB0Ij48ZGVmcz48Y2xpcFBhdGggaWQ9Il9jbGlwUGF0aF9BZFhPRm9XSk9wRVRsWVk0azZkSlFyYWROSERMZEpUQyI+PHJlY3Qgd2lkdGg9IjI2My4zNjYiIGhlaWdodD0iMjUxLjUyIi8+PC9jbGlwUGF0aD48L2RlZnM+PGcgY2xpcC1wYXRoPSJ1cmwoI19jbGlwUGF0aF9BZFhPRm9XSk9wRVRsWVk0azZkSlFyYWROSERMZEpUQykiPjxwYXRoIGQ9IiBNIDEzMS42OCAwIEMgNjkuNzI4IDAuMDE2IDE2LjE2MyA0My4yMDcgMy4wMTQgMTAzLjc0NyBDIC0xMC4xMzUgMTY0LjI4NyAyMC42ODcgMjI1LjgwNyA3Ny4wNSAyNTEuNTIgTCA5MC42NyAxOTEuMzggQyA2NC41MSAxNzMuNDE3IDUzLjA4OSAxNDAuNTE2IDYyLjQ5MyAxMTAuMjA3IEMgNzEuODk4IDc5Ljg5OSA5OS45MzYgNTkuMjQyIDEzMS42NyA1OS4yNDIgQyAxNjMuNDA0IDU5LjI0MiAxOTEuNDQyIDc5Ljg5OSAyMDAuODQ3IDExMC4yMDcgQyAyMTAuMjUxIDE0MC41MTYgMTk4LjgzIDE3My40MTcgMTcyLjY3IDE5MS4zOCBMIDE4Ni4yOCAyNTEuNTIgQyAyNDIuNjY0IDIyNS44MjEgMjczLjUwNCAxNjQuMjkxIDI2MC4zNTIgMTAzLjczOSBDIDI0Ny4yIDQzLjE4NiAxOTMuNjE0IC0wLjAwNiAxMzEuNjUgMCBMIDEzMS42OCAwIFogIiBmaWxsPSJyZ2IoMjU1LDI1NSwyNTUpIi8+PGNpcmNsZSB2ZWN0b3ItZWZmZWN0PSJub24tc2NhbGluZy1zdHJva2UiIGN4PSIxMzEuNjc5OTY1MjA1ODA3MTIiIGN5PSIxMjUuNzYwMDEwMDgyNTgxMyIgcj0iMzUuNDkwMDAwMDAwMDAwMDEiIGZpbGw9InJnYigyNTUsMjU1LDI1NSkiLz48L2c+PC9zdmc+';

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'register_plugin_menu']);
    }

    public static function register_plugin_menu()
    {
        $menu = add_menu_page(
            WhiteLabel::get_wp_menu_name('GPL Cellar'),
            WhiteLabel::get_wp_menu_name('GPL Cellar'),
            'manage_options',
            'gplcellar',
            ['GPLCellar\App', 'plugin_front_end'],
            WhiteLabel::get_wp_menu_icon(self::$menu_icon),
            '81'
        );

        add_action('admin_print_styles-' . $menu, [__CLASS__, 'load_plugin_styles']);
        add_action('admin_print_scripts-' . $menu, [__CLASS__, 'load_plugin_scripts']);

        # @TODO: maybe implement this? wordpress menu could get out of sync when using our app.
        # add_submenu_page('gplcellar', 'GPL Cellar', 'Plugins', 'manage_options', 'gplcellar',  ['GPLCellar\App', 'plugin_front_end']);
        # add_submenu_page('gplcellar', 'GPL Cellar', 'Themes', 'manage_options', 'gplcellar#/catalog?type=themes',  ['GPLCellar\App', 'plugin_front_end']);
        # add_submenu_page('gplcellar', 'GPL Cellar', 'License', 'manage_options', 'gplcellar#/License',  ['GPLCellar\App', 'plugin_front_end']);
    }

    public static function load_plugin_styles()
    {
        wp_enqueue_style('gplcellar-plugin-styles', plugins_url('/assets/styles.css', GPLCELLAR_BASE_FILE));
    }

    public static function load_plugin_scripts()
    {
        wp_enqueue_script(
            'gplcellar-plugin-app',
            plugins_url('assets/js/app.js', GPLCELLAR_BASE_FILE),
            [],
            GPLCELLAR_VERSION,
            true
        );
    }

    public static function plugin_front_end()
    {
        $php_to_js = array(
            'avatar_url'           => get_avatar_url( get_the_author_meta( 'ID' )),
            'catalog_last_updated' => get_date_from_gmt( date( 'Y-m-d H:i:s', get_option('gplcellar_last_json_update') ), get_option( 'date_format' ). ' H:i:s'),
            'total_counts'   => Database::get_total_counts(),
            'whitelabel.enabled' => WhiteLabel::is_enabled(),
          );
        echo '<script type="application/javascript">var gplcellar_globals=' . json_encode($php_to_js) . ';</script>';
        echo '<div id="app"></div>';
    }

    public static function get_tmp_directory()
    {
        $upload = wp_upload_dir();
        $upload_dir = $upload['basedir'];
        $upload_dir = $upload_dir . '/gplcellar_backups/';
        wp_mkdir_p($upload_dir);
        return $upload_dir;
    }
}
