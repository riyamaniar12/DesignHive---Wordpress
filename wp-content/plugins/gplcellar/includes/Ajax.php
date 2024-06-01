<?php
/**
 * Class file used to for handling ajax requests sent from app.js.
 *
 * The methods in this class are all expected to be called from our frontend
 * application so therefore they should all return a JSON payload so the
 * frontend knows where things failed or succeeded.
 */

namespace GPLCellar;

class Ajax
{
    public static function init()
    {
        add_action('wp_ajax_gplcellar_license_check', [ __CLASS__, 'license_check']);
        add_action('wp_ajax_gplcellar_license_info', [ __CLASS__, 'license_info']);
        add_action('wp_ajax_gplcellar_license_activate', [ __CLASS__, 'license_activate']);
        add_action('wp_ajax_gplcellar_license_deactivate', [ __CLASS__, 'license_deactivate']);
        add_action('wp_ajax_gplcellar_download_item', [__CLASS__, 'download_item' ]);
        add_action('wp_ajax_gplcellar_install_plugin', [ __CLASS__, 'install_plugin' ]);
        add_action('wp_ajax_gplcellar_activate_plugin', [__CLASS__, 'activate_plugin' ]);
        add_action('wp_ajax_gplcellar_update_plugin', [ __CLASS__, 'update_plugin' ]);
        add_action('wp_ajax_gplcellar_install_theme', [__CLASS__, 'install_theme' ]);
        add_action('wp_ajax_gplcellar_activate_theme', [ __CLASS__, 'activate_theme' ]);
        add_action('wp_ajax_gplcellar_update_theme', [ __CLASS__, 'update_theme' ]);
        add_action('wp_ajax_gplcellar_get_items', [ __CLASS__, 'get_items']);
        add_action('wp_ajax_gplcellar_refresh_catalog', [ __CLASS__, 'refresh_catalog']);
        add_action('wp_ajax_gplcellar_request_update', [ __CLASS__, 'request_update']);
        add_action('wp_ajax_gplcellar_save_white_label_options', [ __CLASS__, 'save_white_label_options']);
        add_action('wp_ajax_gplcellar_get_white_label_options', [ __CLASS__, 'get_white_label_options']);
    }

    public static function license_check()
    {
        $product_id = $_POST['product_id'];
        $api_key    = $_POST['license_key'];

        $payload = License::instance()->check($product_id, $api_key);

        self::return_to_browser($payload['success'], $payload['msg'], $payload);
    }

    /**
     * Cached license info.
     */
    public static function license_info()
    {
        $payload = array(
                'status' => License::instance()->account_status(),
                'product_id' => License::instance()->get_product_id(),
                'license_key' => License::instance()->get_license(),
        );

        self::return_to_browser(true, '', $payload);
    }

    public static function license_activate()
    {
        $product_id = $_POST['product_id'];
        $api_key    = $_POST['license_key'];

        $response = License::instance()->activate($product_id, $api_key);

        if ($response && $response['success'] == true) {
            $payload = array(
            'success' => true,
            'title'   => 'License Activated!',
            'message' => $response['message'],
            'accountStatus' => $response['accountStatus'],
          );
          self::return_to_browser(true, $response['message'], $payload);
        } else {
            $payload = array(
            'title'   => 'Error Encountered!',
            'message' => $response['error'],
            'accountStatus' => isset($response['accountStatus']) ?  $response['accountStatus'] : '',
          );
          self::return_to_browser(false, isset($response['error']) ? $response['error'] : '', $payload);
        }
    }

    public static function license_deactivate()
    {
        $response = License::instance()->deactivate();

        if ($response && $response['success'] == true) {
            $payload = array(
            'success' => true,
            'title'   => 'License Deactivated!',
            'message' => 'This license is available  to use again.',
            'accountStatus' => $response['accountStatus'],
          );
          self::return_to_browser(true, '', $payload);
        } else {
            $payload = array(
            'success' => false,
            'title'   => 'Error Encountered!',
            'message' => array_key_exists('error', $response) ? $response['error'] : 'Error deactivating' ,
            'accountStatus' => array_key_exists('accountStatus', $response) ? $response['accountStatus'] : '',
          );
          self::return_to_browser(false, array_key_exists('error', $response) ? $response['error'] : 'Error deactivating', $payload);
        }
    }

    public static function request_update()
    {
        $product_id = $_POST['product_id'];

        try {
            $r = Api::request_update($product_id);

            //self::return_to_browser($r['success'], $r['msg']);
        }
        catch( exception $e) {
            Log::write($e->getMessage());
            
            //self::return_to_browser(false, 'Request to update failed.');
        }
        finally {
            self::return_to_browser(true, 'Request sent.');
        }
        
    }

    public static function download_item()
    {
        $product_id = $_POST['product_id'];

        $r = Api::fetch_download_link($product_id);

        if ($r['success'] == true) {
          // our API returned an error to display
          if (array_key_exists('error', $r) && $r['error']) {
            self::return_to_browser(false, $r['error'], $payload);
          }

          $payload = array(
            'link' => $r['link'],
            'filename' => $r['filename']
          );

          self::return_to_browser($r['success'], $r['msg'], $payload);
        } else {
          self::return_to_browser($r['success'], $r['msg']);
        }
    }

    public static function install_plugin()
    {
        $product_id = $_POST['product_id'];

        $zip = self::download_product($product_id);

        $result = Plugin::install($product_id, $zip);

        self::return_to_browser($result);
    }

    public static function activate_plugin()
    {
        $filepath = $_POST['filepath'];

        $result = Plugin::activate_plugin(urldecode($filepath));

        self::return_to_browser($result);
    }

    public static function update_plugin()
    {
        $product_id = $_POST['product_id'];
        $slug = urldecode($_POST['filepath']);

        $zip = self::download_product($product_id);

        $result = Plugin::update($product_id, $slug, $zip);

        self::return_to_browser($result);
    }

    public static function install_theme()
    {
        $item_id = $_POST['product_id'];

        $zip = self::download_product($item_id);

        $result = Theme::install($item_id, $zip);

        self::return_to_browser($result);
    }

    public static function update_theme()
    {
        $item_id = $_POST['product_id'];

        $zip = self::download_product($item_id);

        $result = Theme::update($zip);

        self::return_to_browser($result);
    }

    public static function activate_theme()
    {
        $slug = urldecode($_POST['filepath']);

        $short_slug = explode('/', $slug);
        $short_slug = $short_slug[0];

        $result = Theme::activate($short_slug);

        self::return_to_browser($result);
    }

    /**
     * Handles getting items to display our catalog. $_POST object should
     * be our app filters.
     *
     * @TODO: move the query in this code to the Database class.
     */
    public static function get_items()
    {
        global $wpdb;

        $table = Database::get_table();

        $query = "SELECT * FROM " . $table . " ";
        $queryNum = "SELECT COUNT(*) FROM " . $table . " ";

        // FILTER
        if (isset($_POST['type'])) {
            $type = $_POST['type'];
        }

        // Categories
        if (isset($_POST['category']) && $_POST['category'] != '') {
            $category_id = $_POST['category'];
            $cat_table = Database::get_cat_table();

            $query .= " INNER JOIN " . $cat_table . " ON " . $table . ".product_id=" . $cat_table. ".product_id AND " . $cat_table. ".category_id=" . $category_id;
            $queryNum .= " INNER JOIN " . $cat_table . " ON " . $table . ".product_id=" . $cat_table. ".product_id AND " . $cat_table. ".category_id=" . $category_id;
        }

        if (isset($type) && $type != "") {
            $query .= " WHERE type = '$type' ";
            $queryNum .= " WHERE type = '$type' ";
        } else {
            $query .= " WHERE 1=1 ";
            $queryNum .= " WHERE 1=1 ";
        }

        // SEARCH ITEMS
        if (isset($_POST['search']) && $_POST['search']!= '') {
            $s = esc_sql($_POST['search']);
            $query .= " AND (item_name LIKE '%$s%' OR excerpt LIKE '%$s%' OR filepath LIKE '%$s%') ";
            $queryNum .= " AND (item_name LIKE '%$s%' OR excerpt LIKE '%$s%' OR filepath LIKE '%$s%') ";
        }


        if (isset($_POST['filter']) && $_POST['filter'] != ''  && $_POST['filter'] == 'free') {
            $query .= " AND  is_free = 1 ";
            $queryNum .= " AND is_free = 1 ";
        }


        if (isset($_POST['filter']) && $_POST['filter'] != '' && $_POST['filter'] == 'installed') {
            $installed_plugins = array_keys(get_plugins());
            $installed_wp_themes = wp_get_themes();
            $installed_themes = array();
            foreach ($installed_wp_themes as $theme_slug => $theme_infos) {
                // @NOTE older versions of GPL Cellar do not have full filepath
                $installed_themes[] = $theme_slug;
                // @NOTE value in gplcellar filepath column should have /style.css for themes
                $installed_themes[] = $theme_slug.'/style.css';
            }
            $installed_products = array_merge($installed_themes, $installed_plugins);
            $query .= " AND filepath IN ('".implode("','", $installed_products)."')";
            $queryNum .= " AND filepath IN ('".implode("','", $installed_products)."')";
        }
        $items_per_page = isset($_POST['pagesize']) ? abs((int) $_POST['pagesize']) : 24;
        $page = isset($_POST['pagenum']) ? abs((int) $_POST['pagenum']) : 1;
        $offset = ($page * $items_per_page) - $items_per_page;
        $order = ((isset($_POST['order']) && $_POST['order'] != '') ? $_POST['order'] : 'item_name');
        $order_by = ((isset($_POST['order_by']) && $_POST['order_by'] != '') ? $_POST['order_by'] : 'DESC');
        $query =  $query . " ORDER BY ${order} ${order_by} LIMIT ${offset}, ${items_per_page}";

        $results = $wpdb->get_results($query);

        $num = $wpdb->get_var($queryNum);
        $res = array();
        $res['query'] = $query;
        $res['num_rows'] = $num;
        $new_results = array();
        foreach ($results as $result) {
            $filepath = $result->filepath;
            $type = $result->type;
            $version = $result->version;
            $slug = $filepath;
            $short_slug = explode('/', $filepath);
            $short_slug = $short_slug[0];
            if (($result->type == 'plugin' && Plugin::is_plugin_installed($filepath)) || ($result->type == 'theme' && Theme::is_theme_installed($short_slug))) {
                $result->is_installed = 1;
                if (($result->type == 'plugin' && !!is_plugin_active($slug)) || ($result->type == 'theme' && Theme::is_theme_active($short_slug))) {
                    $result->is_active = 1;
                } else {
                    $result->is_active = 0;
                }
                if ($result->type == 'theme') {
                    $result->installed_version = Theme::get_installed_theme_version($short_slug);
                } else {
                    $result->installed_version = Plugin::get_installed_version($slug);
                }
                if (version_compare($version, $result->installed_version) > 0) {
                    $result->needs_updating = 1;
                } else {
                    $result->needs_updating = 0;
                }
            } else {
                $result->is_installed = 0;
                $result->needs_updating = 0;
            }
            $result->can_be_installed = !empty($result->filepath);

            $new_results[] = $result;
        }
        $res['results'] = $new_results;

        // @TODO: use return_to_browser($status, $msg, $payload);
        if ($res) {
            echo json_encode($res);
        }
        die();
    }

    public static function refresh_catalog()
    {
        $cached = get_transient('gplcellar_last_refresh');

        if (false === $cached) {
            set_transient('gplcellar_last_refresh', true, 5 * MINUTE_IN_SECONDS);
            Catalog::get_catalog(true);

            self::return_to_browser(true);
        } else {
            self::return_to_browser(false, 'Please wait 5 minutes before you can refresh again.');
        }
    }

    public static function save_white_label_options()
    {
        if (WhiteLabel::save_options($_POST)) {
            $options = WhiteLabel::get_options();

            self::return_to_browser(true, '', $options);
        }

        self::return_to_browser(false, 'Could not save white label options');
    }

    public static function get_white_label_options()
    {
        $options = WhiteLabel::get_options();

        self::return_to_browser(true, '', $options);
    }

    // ------------------------------------------------------------------------
    // Private Methods
    // ------------------------------------------------------------------------

    private static function download_product($product_id)
    {
        // ------------------------------------
        // Fetch Download Link
        // ------------------------------------
        $payload = Api::fetch_download_link($product_id);

        if ($payload['success'] == false) {
            self::return_to_browser(false, $payload['msg'], $payload);
        }

        $download_link = $payload['link'];
        $filename = $payload['filename'];

        // ------------------------------------
        // Save remote file locally
        // ------------------------------------
        $upload_dir = App::get_tmp_directory();

        if (!self::ends_with($filename, 'zip')) {
            $filename = $filename.'.zip';
        }
        $zip = $upload_dir.$filename;

        $saved_file_locally = Api::save_to_file($download_link, $zip);

        if (!$saved_file_locally) {
            self::return_to_browser(false, 'Could not download item locally');
        }

        return $zip;
    }

    /**
     * Return payload data back to Ajax frontend request.
     *
     * @param bool $success - True if request is good
     * @param string $msg - A mesage to display. Usually if success if false.
     * @param object $data - The data object to return
     */
    private static function return_to_browser($success, $msg='', $data='')
    {
        if (is_string($success)) {
          $msg = $success;
          $success = false;
        }

        $payload = array(
          'success' => $success,
          'msg' => $msg,
          'data' => $data
        );
        echo json_encode($payload);
        wp_die();
    }

    private static function ends_with($haystack, $needle)
    {
        $length = strlen( $needle );
        if( !$length ) {
            return true;
        }
        return substr( $haystack, -$length ) === $needle;
    }
}
