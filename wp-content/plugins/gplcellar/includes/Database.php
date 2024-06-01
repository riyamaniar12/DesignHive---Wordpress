<?php

namespace GPLCellar;

/**
 * GplCellar_Plugin Class
 */
class Database
{
    protected static $db_version = '2.0';

    public static function init()
    {
    }

    public static function get_table()
    {
        global $wpdb;

        return $wpdb->prefix . "gplcellar_items_tbl";
    }

    public static function get_cat_table()
    {
        global $wpdb;

        return $wpdb->prefix . "gplcellar_items_cat_tbl";
    }

    public static function create()
    {
        $charset_collate = '';
        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        } else {
            $charset_collate = "DEFAULT CHARSET=utf8";
        }
        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE $wpdb->collate";
        }
        $tbl_sql = "CREATE TABLE " . self::get_table() . " (
          id int(12) NOT NULL AUTO_INCREMENT,
          item_name varchar(255) NOT NULL,
          type varchar(255) NOT NULL,
          is_free BOOLEAN NOT NULL DEFAULT FALSE,
          version varchar(255) NOT NULL,
          filepath varchar(255) NOT NULL,
          image varchar(255) NOT NULL,
          excerpt TEXT NOT NULL default '',
          permalink TINYTEXT NOT NULL default '',
          demo TINYTEXT NOT NULL default '',
          update_date DATE NOT NULL,
          count int(12) NOT NULL,
          ordering int(12) NOT NULL,
          product_id int(12) NOT NULL,
          PRIMARY KEY  (id)
          )" . $charset_collate . ";";

        $cat_tbl = "CREATE TABLE " . self::get_cat_table() . " (
        product_id int(12) NOT NULL,
        category_id int(12) NOT NULL
      )" . $charset_collate . ";";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($tbl_sql);
        dbDelta($cat_tbl);

        update_option("gplcellar_db_version", self::$db_version);
    }

    public static function uninstall()
    {
        if (self::table_exists()) {
            global $wpdb;
            $table_name = self::get_table();
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }

        if (self::cat_table_exists()) {
            global $wpdb;
            $table_name = self::get_cat_table();
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }
        delete_option('gplcellar_db_version');
        delete_transient('gpldb_unique_categories');
        delete_transient('gpldb_plugin_count');
    }

    public static function update_check()
    {
        if (!self::table_exists() || !self::cat_table_exists() || get_option('gplcellar_db_version') != self::$db_version) {
            self::create();
            return true;
        }
        return false;
    }

    public static function update($json_articles)
    {
        global $wpdb;
        $table_name = self::get_table();
        $wpdb->query('TRUNCATE TABLE '.$table_name);

        //$chunk_size = ceil(count($json_articles) / 100); // 100 = batch size
        $chunk_size = 100;
        $chunked_array = array_chunk($json_articles, $chunk_size);

        foreach ($chunked_array as $key => $items) {
            $sql = "INSERT INTO " . $table_name . "
                  (item_name, type, version, filepath, image, excerpt, permalink, demo, update_date, ordering, product_id, is_free)
                  VALUES ";

            foreach ($items as $key => $value) {
                $sql .= $wpdb->prepare(
                    "('%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%d','%d'),",
                    $value->item_name,
                    $value->type,
                    $value->version,
                    $value->filepath,
                    $value->image,
                    $value->excerpt,
                    $value->permalink,
                    $value->demo,
                    $value->update_date,
                    $value->ordering,
                    $value->product_id,
                    $value->is_free,
                    );
            }

            $sql = rtrim($sql, ',') . ';';

            $success = $wpdb->query($sql);

            /*if ($success !== $chunk_size) {
                Log::write($success);
                Log::write($sql);
            }*/
        }

        delete_transient('gpldb_plugin_count');

        // Log::write(Database::get_total_counts());
    }

    public static function update_cats($array_articles)
    {
        global $wpdb;
        $table_name = self::get_cat_table();
        $wpdb->query('TRUNCATE TABLE '.$table_name);

        $sql = "INSERT INTO " . $table_name . "
              (product_id, category_id)
              VALUES ";

        foreach ($array_articles as $value) {
            $sql .= $wpdb->prepare(
                "('%d','%d'),",
                $value[0],
                $value[1],
                );
        }

        $sql = rtrim($sql, ',') . ';';

        delete_transient('gpldb_unique_categories');

        return $wpdb->query($sql);
    }

    public static function table_exists()
    {
        global $wpdb;
        $table_name = self::get_table();
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));
        if ($wpdb->get_var($query) != $table_name) {
            return false;
        } else {
            return true;
        }
    }

    public static function cat_table_exists()
    {
        global $wpdb;
        $table_name = self::get_cat_table();
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));
        if ($wpdb->get_var($query) != $table_name) {
            return false;
        } else {
            return true;
        }
    }

    public static function get_plugin_row($slug)
    {
        global $wpdb;
        $table_name = self::get_table();
        $query = "SELECT * FROM " . $table_name . " WHERE filepath = '%s'";
        $query = $wpdb->prepare($query, $slug);
        $product = $wpdb->get_row($query);

        if ($product) {
            return $product;
        }
    }

    public static function get_item($id)
    {
        global $wpdb;
        $table_name = self::get_table();
        $query = "SELECT * FROM " . $table_name . " WHERE product_id = %d";
        $query = $wpdb->prepare($query, $id);
        return $wpdb->get_row($query);
    }

    public static function get_installed_gpl_plugins($installed_plugins)
    {
        global $wpdb;
        $table_name = self::get_table();
        $query = "SELECT *  FROM " . $table_name . " WHERE type = 'plugin' AND filepath IN ('".implode("','", $installed_plugins)."')";
        return $wpdb->get_results($query);
    }

    public static function get_unique_categories($type)
    {
        global $wpdb;

        $categories = get_transient('gpldb_unique_categories');

        if (false === $categories) {
            $table_name = self::get_cat_table();
            $product_table = self::get_table();

            $query = "SELECT distinct(category_id) as id, count(*)
                      FROM $table_name
                      WHERE product_id in (
                      	select product_id from $product_table where type='".$type."'
                      )
                      GROUP BY category_id
                      ORDER BY category_id";

            $categories = $wpdb->get_results($query);

            set_transient('gpldb_unique_categories', $categories, 24 * HOUR_IN_SECONDS);
        }

        return $categories;
    }

    public static function get_total_counts()
    {
        global $wpdb;

        $counts = get_transient('gpldb_plugin_count');

        if (false === $counts) {
            $product_table = self::get_table();

            $query = "SELECT  type,
                              count(*) as total_count
                      FROM $product_table

                      GROUP BY type";

            $results = $wpdb->get_results($query);

            $counts = array();
            foreach ($results as $r) {
              $counts[$r->type] = (int)$r->total_count;
            }

            set_transient('gpldb_plugin_count', $counts, 24 * HOUR_IN_SECONDS);
        }

        return $counts;
    }
}
