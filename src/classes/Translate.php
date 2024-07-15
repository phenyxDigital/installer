<?php

/**
 * Class Translate
 *
 * @since 1.8.1.0
 */
class Translate {
    
     public static function getAdminTranslation($string, $class = 'Phenyx', $addslashes = false, $htmlentities = true, $sprintf = null) {

        global $_LANGINSTALL;
         
         $iso = isset(Context::getContext()->language->iso) ? Context::getContext()->language->iso : 'en';
         
         if (file_exists(_EPH_TRANSLATION_DIR_ . $iso . '/install.php')) {
             include_once _EPH_TRANSLATION_DIR_ . $iso . '/install.php';
         }

        $string = preg_replace("/\\\*'/", "\'", $string);

        $key = md5($string);

        if (isset($_LANGINSTALL[$class . $key])) {

            $str = $_LANGINSTALL[$class . $key];

        }   else {
            $str = Translate::getGenericAdminTranslation($string, $_LANGINSTALL, $key);
        }

        if ($htmlentities) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }

        $str = str_replace('"', '&quot;', $str);

        if ($sprintf !== null) {
            $str = Translate::checkAndReplaceArgs($str, $sprintf);
        }

        return ($addslashes ? addslashes($str) : stripslashes($str));
    }


    public static function checkAndReplaceArgs($string, $args) {

        if (preg_match_all('#(?:%%|%(?:[0-9]+\$)?[+-]?(?:[ 0]|\'.)?-?[0-9]*(?:\.[0-9]+)?[bcdeufFosxX])#', $string, $matches) && !is_null($args)) {

            if (!is_array($args)) {
                $args = [$args];
            }

            return vsprintf($string, $args);
        }

        return $string;
    }


    public static function getInstallerTranslation($string, $class, $sprintf = null, $addslashes = false, $htmlentities = true) {

        global $_LANGINSTALL;

        if ($_LANGINSTALL == null) {

            $iso = isset(Context::getContext()->language->iso) ? Context::getContext()->language->iso : 'en';
            if (file_exists(_EPH_TRANSLATION_DIR_ . $iso .'.php')) {
                include_once _EPH_TRANSLATION_DIR_ . $iso .'.php';
            }


        }
        $str = $string;
        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = md5($string);
        if (isset($_LANGINSTALL[$class .'_'. $key])) {
            $str = $_LANGINSTALL[$class .'_'. $key];
        } 

        if ($htmlentities) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }

        $str = str_replace('"', '&quot;', $str);

        if ($sprintf !== null) {
            $str = Translate::checkAndReplaceArgs($str, $sprintf);
        }

        return ($addslashes ? addslashes($str) : stripslashes($str));
    }
    
    public static function postProcessTranslation($string, $params) {

        // If tags were explicitely provided, we want to use them *after* the translation string is escaped.

        if (!empty($params['tags'])) {

            foreach ($params['tags'] as $index => $tag) {
                // Make positions start at 1 so that it behaves similar to the %1$d etc. sprintf positional params
                $position = $index + 1;
                // extract tag name
                $match = [];

                if (preg_match('/^\s*<\s*(\w+)/', $tag, $match)) {
                    $opener = $tag;
                    $closer = '</' . $match[1] . '>';

                    $string = str_replace('[' . $position . ']', $opener, $string);
                    $string = str_replace('[/' . $position . ']', $closer, $string);
                    $string = str_replace('[' . $position . '/]', $opener . $closer, $string);
                }

            }

        }

        return $string;
    }
    
    public static function getGenericAdminTranslation($string, &$langArray, $key = null) {

        $string = preg_replace("/\\\*'/", "\'", $string);

        if (is_null($key)) {
            $key = md5($string);
        }

        if (isset($langArray['InstallController' . $key])) {
            $str = $langArray['InstallController' . $key];
        } else
        if (isset($langArray['InstallControllerHttp' . $key])) {
            $str = $langArray['InstallControllerHttp' . $key];
        }  else {
            // note in 1.5, some translations has moved from AdminXX to helper/*.tpl
            $str = $string;
        }

        return $str;
    }
    

}
