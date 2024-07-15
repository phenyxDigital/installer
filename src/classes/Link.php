<?php


class Link {

   
    public function getBaseLink($ssl = null, $relativeProtocol = false) {

       
        if ($ssl === null) {


            $ssl = false;
        }

        $company= $_SERVER['HTTP_HOST'];

        if ($relativeProtocol) {
            	$base = '//' . $company;
        	} else {
            	$base = (($ssl && self::isSecure()) ? 'https://' . $company : 'http://' . $company);
        	}
            
        return $base . '/';
    }
    
    public static function isSecure() {
        return
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }
	
}
