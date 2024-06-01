<?php

namespace GPLCellar;

class Catalog
{
    public static $catalog_url = 'https://files.gplcellar.com/gplcellar/gplcellar_db_updates.zip';

    public static function init()
    {
        add_action('gplcellar_daily_update', [__CLASS__, 'get_catalog'], 10);
    }

    public static function uninstall()
    {
        delete_option('gplcellar_last_json_update');
        self::clear_schedule_update();
    }

    public static function schedule_update()
    {
        if (! wp_next_scheduled('gplcellar_daily_update')) {
            wp_schedule_event(time(), 'daily', 'gplcellar_daily_update');
        }
    }

    public static function clear_schedule_update()
    {
        wp_clear_scheduled_hook('gplcellar_daily_update');
    }

    public static function get_catalog($force=false)
    {
        $last_json_update = get_option('gplcellar_last_json_update');
        $difference = '';

        if ($last_json_update) {
            $difference = self::get_time_difference($last_json_update, time());
        }
        if (isset($_POST['force_update']) && $_POST['force_update'] == 'gPeElc3114r') {
            $force = true;
        }

        if ($force || (!$last_json_update || $difference > 12 * 3600)) {
            $upload_dir = App::get_tmp_directory();

            $categories_file = $upload_dir.'gplcellar_db_categories.txt';
            $updates_file = $upload_dir.'gplcellar_db_updates.txt';
            $zip_updates_file = 'gplcellar_db_updates.zip';

            $zip = $upload_dir.$zip_updates_file;

            $fetched = Api::save_to_file(self::$catalog_url, $zip);

            /**
             * Update Products
             */
            $json_articles = null;
            if (file_exists($zip) && unzip_file($zip, $upload_dir)) {
                if (file_exists($updates_file)) {
                    $updates_file_content = file_get_contents($updates_file);
                    $json_articles = json_decode($updates_file_content);
                    //unlink($updates_file);
                }
            }

            if ($json_articles !== null) {
                $setting_save = Database::update($json_articles);

                if ($setting_save) {
                    update_option('gplcellar_last_json_update', time());
                }
            }

            /**
             * Update Product Categories
             */
            $json_articles = null;
            if (file_exists($zip) && unzip_file($zip, $upload_dir)) {
                if (file_exists($categories_file)) {
                    $updates_file_content = file_get_contents($categories_file);
                    $json_articles = json_decode($updates_file_content);
                    //unlink($categories_file);
                }
            }

            if ($json_articles !== null) {
                $setting_save = Database::update_cats($json_articles);

                if ($setting_save) {
                    update_option('gplcellar_last_json_update', time());
                }
            }

            /**
             * Cleanup zip file
             */
            if (file_exists($zip)) {
                unlink($zip);
            }
            if (file_exists($categories_file)) {
                unlink($categories_file);
            }
            if (file_exists($updates_file)) {
                unlink($updates_file);
            }
        }
    }

    public static function get_time_difference($start, $end)
    {
        $uts['start']      =     $start ;
        $uts['end']        =      $end ;
        if ($uts['start']!==-1 && $uts['end']!==-1) {
            if ($uts['end'] >= $uts['start']) {
                $diff    =    $uts['end'] - $uts['start'];
                return round($diff, 0);
            }
        }
    }
}
