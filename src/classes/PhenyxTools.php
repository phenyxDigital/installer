<?php

use \Curl\Curl;

/**
 * Class PhenyxToolsCore
 *
 * @since 1.9.1.0
 */
class PhenyxTools {

	protected static $instance;

	protected $_url;

	protected $_crypto_key;

	public $context;

	public $license;

	public function __construct() {

		$this->context = Context::getContext();

		$this->_url = 'https://ephenyx.io/api';
		$string = Configuration::get('_EPHENYX_LICENSE_KEY_') . '/' . $this->context->company->domain_ssl;
		$this->_crypto_key = Tools::encrypt_decrypt('encrypt', $string, _PHP_ENCRYPTION_KEY_, _COOKIE_KEY_);

		$this->license = $this->checkLicense();
		$this->context->license = $this->license;

	}

	public static function getInstance() {

		if (!PhenyxTools::$instance) {
			PhenyxTools:$instance = new PhenyxTools();
		}

		return PhenyxTools::$instance;
	}

	public function checkLicense() {

		$data_array = [
			'action'      => 'checkLicence',
			'license_key' => Configuration::get('_EPHENYX_LICENSE_KEY_'),
			'crypto_key'  => $this->_crypto_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setTimeout(6000);
		$curl->post($this->_url, json_encode($data_array));
		return $curl->response;

	}

	public function getPhenyxPlugins() {

		$data_array = [
			'action'      => 'getPhenyxPlugins',
			'license_key' => Configuration::get('_EPHENYX_LICENSE_KEY_'),
			'crypto_key'  => $this->_crypto_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setTimeout(6000);
		$curl->post($this->_url, json_encode($data_array));
		$plugins = $curl->response;

		if (is_array($plugins)) {
			file_put_contents(
				_EPH_CONFIG_DIR_ . 'json/plugin_sources.json',
				json_encode($plugins, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
			);
			return true;
		}

		return false;

	}

	public static function writeNewSettings($version) {

       	$seeting_files = _EPH_CONFIG_DIR_ . 'settings.inc.php';

		$mysqlEngine = (defined('_MYSQL_ENGINE_') ? _MYSQL_ENGINE_ : 'InnoDB');

		copy($seeting_files, str_replace('.php', '.old.php', $seeting_files));
		$confFile = fopen($seeting_files, 'w');
        fwrite($confFile, '<?php' . PHP_EOL. PHP_EOL);

		$caches = ['CacheMemcache', 'CacheApc', 'FileBased', 'AwsRedis', 'CacheMemcached', 'CacheXcache'];
        $current_cache = !(empty(Configuration::get('EPH_PAGE_CACHE_TYPE'))) ? Configuration::get('EPH_PAGE_CACHE_TYPE') : 'FileBased';

		$datas = [
			['_EPH_CACHING_SYSTEM_', (defined('_EPH_CACHING_SYSTEM_') && in_array(_EPH_CACHING_SYSTEM_, $caches)) ? _EPH_CACHING_SYSTEM_ : $current_cache],
			['_DB_NAME_', _DB_NAME_],
			['_MYSQL_ENGINE_', $mysqlEngine],
			['_DB_SERVER_', _DB_SERVER_],
			['_DB_USER_', _DB_USER_],
			['_DB_PASSWD_', _DB_PASSWD_],
			['_DB_PREFIX_', _DB_PREFIX_],
			['_COOKIE_KEY_', _COOKIE_KEY_],
			['_COOKIE_IV_', _COOKIE_IV_],
			['_EPH_CREATION_DATE_', defined("_EPH_CREATION_DATE_") ? _PS_CREATION_DATE_ : date('Y-m-d')],
			['_RIJNDAEL_KEY_', _RIJNDAEL_KEY_],
			['_RIJNDAEL_IV_', _RIJNDAEL_IV_],
			['_EPH_VERSION_', $version],
			['_EPH_VENDOR_DIR_', _EPH_VENDOR_DIR_],
			['_PHP_ENCRYPTION_KEY_', _PHP_ENCRYPTION_KEY_],
		];

		
		if (defined('_FORUM_MODE_')) {
			$datas[] = ['_FORUM_MODE_', _FORUM_MODE_];
		}

		if (defined('_BLOG_MODE_')) {
			$datas[] = ['_BLOG_MODE_', _BLOG_MODE_];
		}

		if (defined('_WIKI_MODE_')) {
			$datas[] = ['_WIKI_MODE_', _WIKI_MODE_];
		}

		if (defined('_EPHENYX_MODE_')) {
			$datas[] = ['_EPHENYX_MODE_', _EPHENYX_MODE_];
		}
        
        

		foreach ($datas as $data) {            
			fwrite($confFile, 'define(\'' . $data[0] . '\', \'' . self::checkString($data[1]) . '\');' . PHP_EOL);
		}

		

		return true;
	}
    
    public static  function checkString($string) {

        
        if (!is_numeric($string)) {
            $string = addslashes($string);
        }
        

        return $string;
    }

}
