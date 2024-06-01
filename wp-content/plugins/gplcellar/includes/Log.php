<?php

/**
 * Class file used to register plugin hooks. Used for plugin activation,
 * deactivating, and deleting/uninstalling.
 */
namespace GPLCellar;

class Log
{
    public static function write($log)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}
