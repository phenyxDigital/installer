<?php

/**
 * Class CookieCore
 *
 * @since 1.9.1.0
 */
class CookieCore {

    // @codingStandardsIgnoreStart
    /** @var array Contain cookie content in a key => value format */
    protected $_content;
    /** @var array Crypted cookie name for setcookie() */
    protected $_name;
    /** @var array expiration date for setcookie() */
    protected $_expire;
    /** @var array Website domain for setcookie() */
    protected $_domain;
    /** @var array Path for setcookie() */
    protected $_path;
    /** @var bool $_modified */
    protected $_modified = false;
    protected $_allow_writing;
    protected $_salt;
    protected $_standalone;
    protected $_secure = false;
	
	protected $_csrf_hash;
    // @codingStandardsIgnoreEnd

    /**
     * Get data if the cookie exists and else initialize an new one
     *
     * @param string      $name Cookie name before encrypting
     * @param string      $path
     *
     * @param string|null $expire
     * @param array|null  $sharedUrls
     * @param bool        $standalone
     * @param bool        $secure
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public function __construct($name, $path = '', $expire = null, $sharedUrls = null, $standalone = false, $secure = false) {

        $this->_content = [];
        $this->_standalone = $standalone;
        $this->_expire = is_null($expire) ? time() + 1728000 : (int) $expire;

        $this->_path = trim(($this->_standalone ? '' : Context::getContext()->company->physical_uri) . $path, '/\\') . '/';

        if ($this->_path[0] != '/') {
            $this->_path = '/' . $this->_path;
        }

        $this->_path = rawurlencode($this->_path);
        $this->_path = str_replace('%2F', '/', $this->_path);
        $this->_path = str_replace('%7E', '~', $this->_path);
        // Take Windows case insensitivity of file paths into account

        if (DIRECTORY_SEPARATOR === '\\') {
            $this->_path = mb_strtolower($this->_path);
        }

        $this->_domain = $this->getDomain($sharedUrls);
        $this->_name = 'ephenyx-' . md5($name . $this->_domain);
        $this->_allow_writing = true;
        $this->_salt = $this->_standalone ? str_pad('', 8, md5('ps' . __FILE__)) : _COOKIE_IV_;
        $this->_secure = (bool) $secure;
		
        $this->update();
    }

    /**
     * @param null $sharedUrls
     *
     * @return bool|string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    protected function getDomain($sharedUrls = null) {

        $r = '!(?:(\w+)://)?(?:(\w+)\:(\w+)@)?([^/:]+)?(?:\:(\d*))?([^#?]+)?(?:\?([^#]+))?(?:#(.+$))?!i';

        if(is_null(Tools::getHttpHost(false, false))) {
            return false;
        }
        if (!preg_match($r, Tools::getHttpHost(false, false), $out) || !isset($out[4])) {
            return false;
        }

        if (preg_match(
            '/^(((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]{1}[0-9]|[1-9]).)' .
            '{1}((25[0-5]|2[0-4][0-9]|[1]{1}[0-9]{2}|[1-9]{1}[0-9]|[0-9]).)' .
            '{2}((25[0-5]|2[0-4][0-9]|[1]{1}[0-9]{2}|[1-9]{1}[0-9]|[0-9]){1}))$/', $out[4]
        )) {
            return false;
        }

        if (!strstr(Tools::getHttpHost(false, false), '.')) {
            return false;
        }

        $domain = false;

        if ($sharedUrls !== null) {

            foreach ($sharedUrls as $sharedUrl) {

                if ($sharedUrl != $out[4]) {
                    continue;
                }

                if (preg_match('/^(?:.*\.)?([^.]*(?:.{2,4})?\..{2,3})$/Ui', $sharedUrl, $res)) {
                    $domain = '.' . $res[1];
                    break;
                }

            }

        }

        if (!$domain) {
            $domain = $out[4];
        }

        return $domain;
    }

    /**
     * Get cookie content
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function update($nullValues = false) {
        
        if (isset($_COOKIE[$this->_name])) {
            /* Decrypt cookie content */
            $content = $this->getCipherTool()->decrypt(Tools::base64UrlDecode($_COOKIE[$this->_name]));
            if ($content) {
                /* Get cookie checksum */
                $tmpTab = explode('¤', $content);
                array_pop($tmpTab);
                $contentForChecksum = implode('¤', $tmpTab) . '¤';
                $checksum = crc32($this->_salt . $contentForChecksum);

                /* Unserialize cookie content */
                $tmpTab = explode('¤', $content);
                foreach ($tmpTab as $keyAndValue) {
                    $tmpTab2 = explode('|', $keyAndValue);
                    if (count($tmpTab2) == 2) {
                        $this->_content[$tmpTab2[0]] = $tmpTab2[1];
                    }
                }
                /* Blowfish fix */
                if (isset($this->_content['checksum'])) {
                    $this->_content['checksum'] = (int)($this->_content['checksum']);
                }
            }

            /* Check if cookie has not been modified */
            if (!isset($this->_content['checksum']) || $this->_content['checksum'] != $checksum) {
                $this->delete();
            }

            if (!isset($this->_content['date_add'])) {
                $this->_content['date_add'] = date('Y-m-d H:i:s');
            }
        } else {
            $this->_content['date_add'] = date('Y-m-d H:i:s');
        }

       

        //checks if the language exists, if not choose the default language

        if (!$this->_standalone && !Language::getLanguage((int) $this->id_lang)) {
            $this->id_lang = Configuration::get('EPH_LANG_DEFAULT');
            // set detect_language to force going through Tools::setCookieLanguage to figure out browser lang
            $this->detect_language = true;
        }

    }
    
    public function delete()  {
        
        $this->_content = [];
        $this->_setcookie();
        unset($_COOKIE[$this->_name]);
        $this->_modified = true;
    }

    /**
     * Delete cookie
     *
     * @deprecated 1.0.0 Use User::logout() or Employee::logout() instead;
     */
    public function logout() {

        $this->_content = [];
        $this->_setcookie();
        unset($_COOKIE[$this->_name]);
        $this->_modified = true;
    }

    /**
     * Setcookie according to php version
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function _setcookie($cookie = null) {

        if ($cookie) {
            $content = $this->getCipherTool()->encrypt($cookie);
            $time = $this->_expire;
        } else {
            $content = 0;
            $time = 1;
        }

        return setrawcookie($this->_name, Tools::base64UrlEncode($content), $time, $this->_path, $this->_domain, $this->_secure, true);
    }

    /**
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function disallowWriting() {

        $this->_allow_writing = false;
    }

    /**
     * Set expiration date
     *
     * @param int $expire Expiration time from now
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setExpire($expire) {

        $this->_expire = (int) ($expire);
    }

    /**
     * Magic method wich return cookie data from _content array
     *
     * @param string $key key wanted
     *
     * @return string value corresponding to the key
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __get($key) {

        return isset($this->_content[$key]) ? $this->_content[$key] : false;
    }

    /**
     * Magic method which adds data into _content array
     *
     * @param string $key   Access key for the value
     * @param mixed  $value Value corresponding to the key
     *
     * @throws Exception
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __set($key, $value) {

        if (is_array($value)) {
            die(Tools::displayError());
        }

        if (preg_match('/¤|\|/', $key . $value)) {
            throw new Exception('Forbidden chars in cookie');
        }

        if (!$this->_modified && (!isset($this->_content[$key]) || (isset($this->_content[$key]) && $this->_content[$key] != $value))) {
            $this->_modified = true;
        }

        $this->_content[$key] = $value;
    }

    /**
     * Magic method which check if key exists in the cookie
     *
     * @param string $key key wanted
     *
     * @return bool key existence
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __isset($key) {

        return isset($this->_content[$key]);
    }

    /**
     * Magic method wich delete data into _content array
     *
     * @param string $key key wanted
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __unset($key) {

        if (isset($this->_content[$key])) {
            $this->_modified = true;
        }

        unset($this->_content[$key]);
    }

    /**
     * Check customer informations saved into cookie and return customer validity
     *
     * @deprecated 1.0.0 use User::isLogged() instead
     *
     * @param bool $withGuest
     *
     * @return bool customer validity
     */
    public function isLogged($withGuest = false) {

        Tools::displayAsDeprecated();

        if (!$withGuest && $this->is_guest == 1) {
            return false;
        }

        /* Customer is valid only if it can be load and if cookie password is the same as database one */

        if ($this->logged == 1 && $this->id_user && Validate::isUnsignedId($this->id_user) && User::checkPassword((int) ($this->id_user), $this->passwd)) {
            return true;
        }

        return false;
    }

    /**
     * Check employee informations saved into cookie and return employee validity
     *
     * @deprecated 1.0.0 use Employee::isLoggedBack() instead
     * @return bool employee validity
     */
    public function isLoggedBack() {

        Tools::displayAsDeprecated();

        /* Employee is valid only if it can be load and if cookie password is the same as database one */

        return ($this->id_employee
            && Validate::isUnsignedId($this->id_employee)
            && Employee::checkPassword((int) $this->id_employee, $this->passwd)
            && (!isset($this->_content['remote_addr']) || $this->_content['remote_addr'] == ip2long(Tools::getRemoteAddr()) || !Configuration::get('EPH_COOKIE_CHECKIP'))
        );
    }

    /**
     * Soft logout, delete everything links to the customer
     * but leave there affiliate's informations.
     *
     * @deprecated 1.0.0 use User::mylogout() instead;
     */
    public function mylogout() {

        unset($this->_content['id_user']);
        unset($this->_content['id_connections']);
        unset($this->_content['user_lastname']);
        unset($this->_content['user_firstname']);
        unset($this->_content['passwd']);
        unset($this->_content['logged']);
        unset($this->_content['email']);
        unset($this->_content['id_employee']);
        unset($this->_content['is_admin']);
        $this->_modified = true;
    }

    /**
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function makeNewLog() {

        unset($this->_content['id_user']);
        unset($this->_content['id_guest']);
        Guest::setNewGuest($this);
        $this->_modified = true;
    }

    /**
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __destruct() {

        $this->write();
    }

    /**
     * Save cookie with setcookie()
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function write() {

        if (!$this->_modified) {
            return true;
        }

        if (headers_sent() || !$this->_allow_writing) {
            return false;
        }

        $cookie = '';

        /* Serialize cookie content */

        if (isset($this->_content['checksum'])) {
            unset($this->_content['checksum']);
        }

        foreach ($this->_content as $key => $value) {
            $cookie .= $key . '|' . $value . '¤';
        }

        /* Add checksum to cookie */
        $cookie .= 'checksum|' . crc32($this->_salt . $cookie);
        $this->_modified = false;

        /* Cookies are encrypted for evident security reasons */

        return $this->_setcookie($cookie);
    }

    /**
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     *
     * @param string $origin
     */
    public function unsetFamily($origin) {

        $family = $this->getFamily($origin);

        foreach (array_keys($family) as $member) {
            unset($this->$member);
        }

    }

    /**
     * Get a family of variables (e.g. "filter_")
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     *
     * @param string $origin
     *
     * @return array
     */
    public function getFamily($origin) {

        $result = [];

        if (count($this->_content) == 0) {
            return $result;
        }

        foreach ($this->_content as $key => $value) {

            if (strncmp($key, $origin, strlen($origin)) == 0) {
                $result[$key] = $value;
            }

        }

        return $result;
    }

    /**
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getAll() {

        return $this->_content;
    }

    /**
     * @return String name of cookie
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getName() {

        return $this->_name;
    }

    /**
     * Check if the cookie exists
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function exists() {

        return isset($_COOKIE[$this->_name]);
    }

    /**
     * Get the cipher tool instance used by this cookie instance
     *
     * @return Blowfish|Rijndael|PhpEncryption
     *
     * @since 1.0.1
     */
    public function getCipherTool() {

        return $this->_standalone ? Encryptor::getStandaloneInstance(__FILE__) : Encryptor::getInstance();
    }
	
	public function get_csrf_hash()
	{
		return $this->_csrf_hash;
	}
	
	protected function _csrf_set_hash()	{
		if ($this->_csrf_hash === NULL)
		{
			// If the cookie exists we will use its value.
			// We don't necessarily want to regenerate it with
			// each page load since a page could contain embedded
			// sub-pages causing this feature to fail
			if (isset($_COOKIE[$this->_name]) && is_string($_COOKIE[$this->_name])
				&& preg_match('#^[0-9a-f]{32}$#iS', $_COOKIE[$this->_name]) === 1)
			{
				return $this->_csrf_hash = $_COOKIE[$this->_name];
			}

			$rand = $this->get_random_bytes(16);
			$this->_csrf_hash = ($rand === FALSE)
				? md5(uniqid(mt_rand(), TRUE))
				: bin2hex($rand);
		}

		return $this->_csrf_hash;
	}
	
	public function get_random_bytes($length) {
		
		if (empty($length) OR ! ctype_digit((string) $length))
		{
			return FALSE;
		}

		if (function_exists('random_bytes'))
		{
			try
			{
				// The cast is required to avoid TypeError
				return random_bytes((int) $length);
			}
			catch (Exception $e)
			{
				// If random_bytes() can't do the job, we can't either ...
				// There's no point in using fallbacks.
				log_message('error', $e->getMessage());
				return FALSE;
			}
		}

		// Unfortunately, none of the following PRNGs is guaranteed to exist ...
		if (defined('MCRYPT_DEV_URANDOM') && ($output = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM)) !== FALSE)
		{
			return $output;
		}


		if (is_readable('/dev/urandom') && ($fp = fopen('/dev/urandom', 'rb')) !== FALSE)
		{
			// Try not to waste entropy ...
			stream_set_chunk_size($fp, $length);
			$output = fread($fp, $length);
			fclose($fp);
			if ($output !== FALSE)
			{
				return $output;
			}
		}

		if (function_exists('openssl_random_pseudo_bytes'))
		{
			return openssl_random_pseudo_bytes($length);
		}

		return FALSE;
	}

}
