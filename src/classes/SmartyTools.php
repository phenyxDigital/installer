<?php

/**
 * Class SmartyTools
 *
 * @since 1.9.1.0
 */
class SmartyTools {

   

    public static function getMemoryLimit() {

        $memory_limit = @ini_get('memory_limit');

        return Tools::getOctets($memory_limit);
    }

    public static function getOctets($option) {

        if (preg_match('/[0-9]+k/i', $option)) {
            return 1024 * (int) $option;
        }

        if (preg_match('/[0-9]+m/i', $option)) {
            return 1024 * 1024 * (int) $option;
        }

        if (preg_match('/[0-9]+g/i', $option)) {
            return 1024 * 1024 * 1024 * (int) $option;
        }

        return $option;
    }
    
    public static function rtrimString($str, $str_search) {

        $length_str = strlen($str_search);

        if (strlen($str) >= $length_str && substr($str, -$length_str) == $str_search) {
            $str = substr($str, 0, -$length_str);
        }

        return $str;
    }

    public static function formatBytes($size, $precision = 2) {

        if (!$size) {
            return '0';
        }

        $base = log($size) / log(1024);
        $suffixes = ['', 'k', 'M', 'G', 'T'];

        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }

    public static function boolVal($value) {

        if (empty($value)) {
            $value = false;
        }

        return (bool) $value;
    }

  
}
