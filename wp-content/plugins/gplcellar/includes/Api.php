<?php

namespace GPLCellar;

/**
 * Responsible for making all outgoing HTTP requests, most likely to our
 * gpl server endpoints.
 *
 * All files needing to make outgoing requests should go through Api.
 */

class Api
{
    /**
     * Returns the temp S3 link to download from.
     *
     * @since 2.2.2
     */
    public static function fetch_download_link($item_id)
    {
        $gplcellar_licence_manager = get_option('gplcellar_plugin_manager');
        $api_key = $gplcellar_licence_manager['api_key'];
        $product_id = $gplcellar_licence_manager['product_id'];
        $instance = $gplcellar_licence_manager['instance'];;

        $url = GPLCELLAR_URL . '?r=download_link&item_id='.$item_id.'&api_key='.$api_key.'&instance='.$instance.'&product_id='.$product_id;

        $response = Api::fetch($url);
        
        $defaults = array(
          'link' => '',
          'filename' => '',
          'success' => false,
          'code' => '',
          'msg' => '',
        );

        $payload = array_merge( $defaults, $response );

        if ($payload['success'] == false) {
            $payload['msg'] = 'Failed to generate download link.';
        }

        return $payload;
    }

    /**
     * Request an update to an item.
     * 
     * @since 3.2.0
     */
    public static function request_update($item_id)
    {
        $gplcellar_licence_manager = get_option('gplcellar_plugin_manager');
        $api_key = $gplcellar_licence_manager['api_key'];
        $product_id = $gplcellar_licence_manager['gplcellar_plugin_manager_product_id'];
        $instance = get_option('gplcellar_plugin_manager_instance');
        
        $url = GPLCELLAR_URL . '?r=request_item_update&item_id='.$item_id.'&api_key='.$api_key.'&instance='.$instance.'&product_id='.$product_id;
        
        // request_item_update has no response data
        Api::fetch($url);
        
        $response = array(
          'success' => true,
          'code' => '',
          'msg' => '',
        );
        
        return $response;
    }


    public static function save_to_file($url, $filename)
    {
        require_once(ABSPATH . '/wp-admin/includes/file.php');
        WP_Filesystem();

        try {
          if( function_exists('curl_init')) {
            $ch = curl_init($url);
            // Open file
            $fp = fopen($filename, 'wb');

            // It set an option for a cURL transfer
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            // Perform a cURL session
            curl_exec($ch);

            // Closes a cURL session and frees all resources
            curl_close($ch);

            // Close file
            fclose($fp);
            return true;
          } else {
            $body = wp_remote_retrieve_body( wp_remote_get( $url ) );
            $fp = fopen($filename, 'wb');
            fwrite($fp, $body);
            fclose($fp);
            return true;
          }

        } catch (Exception $e) {
        }
        return false;
    }

    public static function fetch($url)
    {
        $request = wp_remote_get($url, array('sslverify' => false, 'user-agent' => 'WooCommerce REST API', 'timeout' => 10));

        if (is_wp_error($request) || wp_remote_retrieve_response_code($request) != 200) {
          if (is_array($request)) {
            return array(
              'success' => false,
              'code' => '500',
              'msg' => 'API request failed.'
            );
          } else {
            return array(
              'success' => false,
              'code'  => $request->get_error_code(),
              'msg' => $request->get_error_message(),
            );
          }            
        }

        $response = wp_remote_retrieve_body($request);

        $response = json_decode( $response, true );

        // Our attempt to standardize the rquest
        $defaults = array(
          'success' => true,
          'code'    => 100,
          'msg'     => ''
        );

        if ( $response ) {
          $payload = array_merge( $defaults, $response );
        } else {
          $payload = array(
            'success' => false,
            'code'    => 600,
            'msg'     => 'Api.fetch error.'
          );
        }      

        return $payload;
    }
}
