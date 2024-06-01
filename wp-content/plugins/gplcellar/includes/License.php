<?php
/**
 * Integration with license
 *
 */

namespace GPLCellar;

class License
{

    /**
     * Self Upgrade Values
     */
    // Base URL to the remote upgrade API Manager server. If not set then the Author URI is used.
    public $upgrade_url = GPLCELLAR_URL;

    /**
     * @var string
     */
    public $version = GPLCELLAR_VERSION;

    /**
     * @var string
     * This version is saved after an upgrade to compare this db version to $version
     */
    public $gplcellar_plugin_manager_version_name = 'plugin_gplcellar_plugin_manager_version';

    /**
     * @var string
     */
    public $text_domain = 'gplcellar-plugin-manager';

    /**
     * WordPress options
     */
    public $ame_options_key                  = 'gplcellar_plugin_manager';
    // These are the names of the stored options[keys]
    public $ame_api_key                   = 'api_key';    
    public $ame_product_id_key            = 'product_id';
    public $ame_instance_key              = 'instance';

    /**
     * PLUGIN Status
     */
    public $ame_activation_status         = 'gplcellar_plugin_manager_activated';

    /**
     * Software Product ID is the product title string
     */
    private $ame_software_product_id     = 'GPL Cellar';

    public $ame_options;
    public $ame_plugin_name;
    public $ame_renew_license_url;
    //public $ame_instance_id;
    public $ame_domain;
    public $ame_software_version;
    public $ame_plugin_or_theme;

    /**
     * @var The single instance of the class
     */
    protected static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public static function init()
    {
        //register_activation_hook(GPLCELLAR_BASE_FILE, [ __CLASS__, 'on_activation' ]);
    }

    /**
     * Cloning is forbidden.
     *
     * @since 1.2
     */
    public function __clone()
    {
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.2
     */
    public function __wakeup()
    {
    }

    public function __construct()
    {
        if (is_admin()) {
            // Check for external connection blocking
            //add_action('admin_notices', array( $this, 'check_external_blocking' ));

            /**
             * Set all software update data here
             */
            $this->ame_options 				= get_option($this->ame_options_key);
            //$this->ame_instance_id 			= get_option($this->ame_instance_key); // Instance ID (unique to each blog activation)
            // $this->ame_product_id 			= get_option($this->ame_product_id_key); // Software Title
            $this->ame_plugin_name 			= 'gplcellar/gplcellar.php'; // same as plugin slug. if a theme use a theme name like 'twentyeleven'
            $this->ame_renew_license_url 	= 'https://www.gplcellar.com/account'; // URL to renew a license. Trailing slash in the upgrade_url is required.
            $this->ame_domain 				= str_ireplace(array( 'http://', 'https://' ), '', home_url()); // blog domain name
            $this->ame_software_version 	= $this->version; // The software version
            $this->ame_plugin_or_theme 		= 'plugin';

            // @since 3.2.4 - license check done via API
            // $this->license_check();
        }
    }

    public function get_product_id()
    {
        return isset($this->ame_options[$this->ame_product_id_key]) ? $this->ame_options[$this->ame_product_id_key] : '';
    }

    public function get_license()
    {
        
        return isset($this->ame_options[$this->ame_api_key]) ? $this->ame_options[$this->ame_api_key] : '';
    }

    public function get_instance()
    {   
        //return isset($this->ame_options[$this->ame_instance_key]) ? $this->ame_options[$this->ame_instance_key] : $this->ame_domain;
        return $this->ame_domain;
    }

    /**
     * Generate the default data arrays on plugin activation
     */
    public function activation()
    {
        //ob_start();

        /* @since 3.2.4
        $this->save_license_options(array(
            $this->ame_product_id_key => '',
            $this->ame_api_key => '',
        ));*/

        $single_options = array(
            //$this->ame_product_id_key     => '',
            // @since 3.2.3 - will use domain instead of random string
            //$this->ame_instance_key 	  => wp_generate_password(12, false),
            //$this->ame_instance_key => $this->ame_domain,
            $this->ame_activation_status  => 'deactivate',
        );

        foreach ($single_options as $key => $value) {
            update_option($key, $value);
        }

        $curr_ver = get_option($this->gplcellar_plugin_manager_version_name, 0);

        // checks if the current plugin version is lower than the version being installed
        if (version_compare($this->version, $curr_ver, '>')) {
            // update the version
            update_option($this->gplcellar_plugin_manager_version_name, $this->version);
        }

        //trigger_error(ob_get_contents(),E_USER_ERROR);
    }

    /**
     * @since 3.4.1 - Created Auto activate license method using config
     */
    public function auto_activate()
    {
        // @since 3.4.0 - Auto activate using Config
        $config = Configs::read_config();

        if ($config['API_KEY'] != '' and $config['PRODUCT_ID'] != '') {
            $response = self::instance()->activate($config['PRODUCT_ID'], $config['API_KEY']);
            if ($response['success'] == false or $response['accountStatus'] != 'active') {
                Log::write($response);
                Log::write('ERROR: License failed to activate on plugin activation.');
            }
        }
    }

    /**
     * Deletes all data if plugin deactivated
     * @return void
     */
    public function uninstall()
    {
        global $wpdb, $blog_id;

        $this->deactivate();

        // Remove options
        if (is_multisite()) {
            switch_to_blog($blog_id);

            foreach (array(
                    $this->ame_options_key,
                    //$this->ame_product_id_key,
                    //$this->ame_instance_key,
                    $this->ame_activation_status,
                    ) as $option) {
                delete_option($option);
            }

            restore_current_blog();
        } else {
            foreach (array(
                    $this->ame_options_key,
                    //$this->ame_product_id_key,
                    //$this->ame_instance_key,
                    $this->ame_activation_status
                    ) as $option) {
                delete_option($option);
            }
        }

        delete_transient('gpldb_license_key_check');
        delete_option($this->gplcellar_plugin_manager_version_name);
    }


    /**
     * Returns the API License Key status from the WooCommerce API Manager on the server
     *
     * @param  array $args
     * @return response
     * {
     *  "success": true,
     *  "code": 100,
     *  "status_check": "inactive",
     *  "data": {
     *    "unlimited_activations": false,
     *    "total_activations_purchased": 1000,
     *    "total_activations": 4,
     *    "activations_remaining": 996,
     *    "activated": false
     *  },
     *  "api_call_execution_time": "0.014989 seconds"
     * }
     */
    public function check($product_id, $api_key)
    {
        $args = array(
            'request' 		=> 'status',
            'product_id' 	=> $product_id,
            'api_key' 	    => $api_key,
            'instance' 		=> $this->get_instance(),
            'platform' 		=> $this->ame_domain
        );

        $target_url = esc_url_raw($this->create_software_api_url($args));

        $response = Api::fetch($target_url);

        return $response;
    }

    /**
     *
     */
    public function activate($product_id, $api_key)
    {
        /**
         * Instance ID might be empty when activating.
         * 
         * @since 3.2.3
         */
        /*if ($this->ame_instance_id == '') {
            //$this->ame_instance_id = wp_generate_password(12, false);
            $this->ame_instance_id = $this->ame_domain;

            update_option($this->ame_instance_key, $this->ame_instance_id);
        }*/

        $instance_id = $this->get_instance();

        $args = array(
            'request' 			=> 'activation',
            'product_id' 		=> $product_id,
            'api_key'           => $api_key,
            'instance' 			=> $instance_id,
            'platform' 			=> $this->ame_domain,
            'software_version' 	=> $this->ame_software_version,
        );

        $target_url = esc_url_raw($this->create_software_api_url($args));

        $response = Api::fetch($target_url);

        if ($response['success'] == true) {
            $response['accountStatus'] = 'active';

            update_option($this->ame_activation_status, $response['accountStatus']);

            $this->save_license_options(array(
                $this->ame_instance_key     => $instance_id,
                $this->ame_product_id_key   => $args['product_id'],
                $this->ame_api_key          => $args['api_key'],
            ));

            delete_transient('gpldb_license_key_check');
        }

        return $response;
    }

    public function deactivate()
    {
        $api_key = $this->get_license();
        $product_id = $this->get_product_id();
        $instance = $this->get_instance();

        $args = array(
            'product_id' => $product_id,
            'api_key'    => $api_key,
            'instance'   => $instance,
        );

        $defaults = array(
            'request' 		=> 'deactivation',
            'platform' 		=> $this->ame_domain
        );

        $args = wp_parse_args($defaults, $args);

        $target_url = esc_url_raw($this->create_software_api_url($args));

        $response = Api::fetch($target_url);
        
        Log::write($target_url);
        Log::write($response);

        // deactivate regardless of response
        // @since 3.1.3
        // re-enabled
        // @since 3.2.4
        if ($response['success'] == true) {
            $response['accountStatus'] = 'deactive';

            update_option($this->ame_activation_status, $response['accountStatus']);

            // @since 3.2.4 preserve api key and product id on deactivation
            /*$this->save_license_options(array(
              $this->ame_product_id_key   => '',
              $this->ame_api_key          => '',
            ));*/

            delete_transient('gpldb_license_key_check');
        }

        return $response;
    }

    /**
     * Convenience method to deactive and reactivate our plugin.
     * This helps sync the user account on gplcellar.com server with
     * the correct plugin version.
     */
    public function reactivate()
    {
        $product_id = $this->get_product_id();
        $api_key = $this->get_license();

        $this->deactivate();

        $this->activate($product_id, $api_key);
    }

    public function account_status()
    {
        $status = get_option($this->ame_activation_status);
        // normalize. @since 3.0 $this->ame_activation_status was Activated
        if ($status == 'Activated') {
          $status = 'active';
        }
        return $status;
    }


    //-------------------------------------------------------------------------
    // PRIVATE METHODS
    //-------------------------------------------------------------------------

    /**
     * Saves our license data in WordPress options table.
     */
    private function save_license_options($options)
    {
        $this->ame_options = $options;
    
        update_option($this->ame_options_key, $options);
    }


    /**
     * Generates our API Key URL
     */
    private function create_software_api_url($args)
    {
        $api_url = add_query_arg('wc-api', 'wc-am-api', $this->upgrade_url);

        return $api_url . '&' . http_build_query($args);
    }

    /**
     * @deprecated - License check is done on API request.
     * @since 3.2.4
     * 
     * Perform periodic license check because a license could become invalid
     * after a subscription expires.
     */
    private function license_check()
    {
        $time_to_check_license = get_transient('gpldb_license_key_check');

        if ($time_to_check_license === false) {
            $product_id = $this->get_product_id();
            $api_key =$this->get_license();

            $response = $this->check($product_id, $api_key);

            if ($response['success'] == true) {
                if ($response['status_check'] == 'inactive') {
                    update_option($this->ame_activation_status, 'deactive');
                }
            }

            // perform a license check ever 10 hours
            set_transient('gpldb_license_key_check', true, 1 * HOUR_IN_SECONDS);
        }
    }
} // End of class
