<?php

namespace GPLCellar;

class Theme
{
    public static function init()
    {
    }

    /**
     * @param $product_id {int} - The item product_id
     *
     * @return bool - True or false if the item was installed.
     */
    public static function install($product_id, $zip)
    {
        $themes_path_url = get_theme_root();
        $themes_path = trailingslashit($themes_path_url);

        $args = array(
                'path' => $themes_path,
                'preserve_zip' => false
            );

        $item = Database::get_item($product_id);
        if ($item) {
            $short_slug = explode('/', $item->filepath);
            $short_slug = $short_slug[0];

            if (self::is_theme_installed($short_slug)) {
                return "Error 1003"; // Theme already exists
            }

            if ($item->is_free != 1 && License::instance()->account_status() != 'active') {
                return "Error 1006 - item is not free and license is not active.";
            }

            if ($zip) {
                WP_Filesystem();

                $unzipfile = unzip_file($zip, $args['path']);

                if (!$unzipfile) {
                    return "Error 1008 - could not unzip theme"; // Error WP Unzip
                }

                if ($args['preserve_zip'] === false) {
                    unlink($zip);
                }
            } else {
                return "Error 1001 - failed to save zip file. Try downloading manually. "; // 1001 failed to put zip file in directory
            }
        } else {
            return 'Error 1002 - could not find theme.';
        }

        return true;
    }

    public static function activate($slug)
    {
        if (self::is_theme_installed($slug) && !self::is_theme_active($slug)) {
            $activate = switch_theme($slug);
            if (is_wp_error($error) && ! empty($error->errors)) {
                return false;
            } else {
                return true;
            }
        }
        return true;
    }

    public static function update($zip)
    {
        if ($zip) {
            WP_Filesystem();

            $themes_path_url = get_theme_root();
            $themes_path = trailingslashit($themes_path_url);

            $args = array(
                  'path' => $themes_path,
                  'preserve_zip' => false
              );

            $unzipfile = unzip_file($zip, $args['path']);

            if (!$unzipfile) {
                return "Error 1008 - could not unzip theme"; // Error WP Unzip
            }

            if ($args['preserve_zip'] === false) {
                unlink($zip);
            }
        } else {
            return "Error 1001 - failed to save zip file. Try downloading manually. "; // 1001 failed to put zip file in directory
        }

        return true;
    }

    public static function is_theme_installed($slug)
    {
        $installed_themes = wp_get_themes();
        return (! empty($installed_themes[ $slug ]));
    }

    public static function is_theme_active($slug)
    {
        $current_theme = wp_get_theme();
        if ($current_theme && self::is_theme_installed($slug)) {
            if ($current_theme->stylesheet == $slug) {
                return true;
            }
        }
        return false;
    }

    public static function get_installed_theme_version($slug)
    {
        $installed_themes = wp_get_themes();
        if (! empty($installed_themes[ $slug ])) {
            return $installed_themes[$slug ]['Version'];
        }
        return '';
    }
}
