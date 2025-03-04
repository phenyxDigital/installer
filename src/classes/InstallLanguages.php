<?php

/**
 * Class InstallLanguages
 *
 * @since 1.0.0
 */
class InstallLanguages {

    const DEFAULT_ISO = 'en';
    protected static $instance;
    /**
     * @var array List of available languages
     */
    protected $languages;
    /**
     * @var string Current language
     */
    protected $language;
    /**
     * @var InstallLanguage Default language (english)
     */
    protected $default;

    /**
     * InstallLanguages constructor.
     *
     * @throws PhenyxInstallerException
     *
     * @since 1.0.0
     */
    public function __construct() {

       
        // English language is required

        if (!file_exists(_EPH_INSTALL_LANGS_PATH_ . 'en/language.xml')) {
            throw new PhenyxInstallerException('English language is missing');
        }

        $this->languages = [
            self::DEFAULT_ISO => new InstallLanguage(self::DEFAULT_ISO),
        ];

        // Load other languages

        foreach (scandir(_EPH_INSTALL_LANGS_PATH_) as $lang) {

            if ($lang[0] != '.' && is_dir(_EPH_INSTALL_LANGS_PATH_ . $lang) && $lang != self::DEFAULT_ISO && file_exists(_EPH_INSTALL_LANGS_PATH_ . $lang . '/install.php')) {
                $this->languages[$lang] = new InstallLanguage($lang);
            }

        }
       

        uasort($this->languages, ['InstallLanguages', 'psUsortLanguages']);
         
    }

    /**
     * @return InstallLanguages
     *
     * @since 1.0.0
     * @throws PhenyxInstallerException
     */
    public static function getInstance() {

        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get current language
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getLanguageIso() {

        return $this->language;
    }

    /**
     * Get list of languages iso supported by installer
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getLanguages() {

        return $this->languages;
    }

    /**
     * Get translated string
     *
     * @param string $str String to translate
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function l($str) {

        $args = func_get_args();
        $translation = $this->getLanguage()->getTranslation($args[0]);

        if (is_null($translation)) {
            $translation = $this->getLanguage(self::DEFAULT_ISO)->getTranslation($args[0]);

            if (is_null($translation)) {
                $translation = $args[0];
            }

        }

        $args[0] = $translation;

        if (count($args) > 1) {
            return call_user_func_array('sprintf', $args);
        } else {
            return $translation;
        }

    }

    /**
     * Get current language
     *
     * @param null $iso
     *
     * @return InstallLanguage
     *
     * @since 1.0.0
     */
    public function getLanguage($iso = null) {

        if (!$iso) {
            $iso = $this->language;
        }

        return $this->languages[$iso];
    }

    /**
     * Set current language
     *
     * @param string $iso Language iso
     *
     * @throws PhenyxInstallerException
     *
     * @since 1.0.0
     */
    public function setLanguage($iso) {

        if (!in_array($iso, $this->getIsoList())) {
            throw new PhenyxInstallerException('Language ' . $iso . ' not found');
        }

        $this->language = $iso;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getIsoList() {

        return array_keys($this->languages);
    }
    
    public function getLanguageList() {

        return Tools::jsonDecode(Tools::jsonEncode($this->languages), 1);
    }

    /**
     * Get an information from language (phone, links, etc.)
     *
     * @param string $key         Information identifier
     * @param bool   $withDefault
     *
     * @return null
     *
     * @since 1.0.0
     */
    public function getInformation($key, $withDefault = true) {

        $information = $this->getLanguage()->getTranslation($key, 'informations');

        if (is_null($information) && $withDefault) {
            return $this->getLanguage(self::DEFAULT_ISO)->getTranslation($key, 'informations');
        }

        return $information;
    }

    /**
     * Get list of countries for current language
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getCountries() {

        static $countries = null;

        if (is_null($countries)) {
            $countries = [];
            $countriesLang = $this->getLanguage()->getCountries();
            $countriesDefault = $this->getLanguage(self::DEFAULT_ISO)->getCountries();
            $xml = @simplexml_load_file(_EPH_INSTALL_DATA_PATH_ . 'xml/country.xml');

            if ($xml) {

                foreach ($xml->entities->country as $country) {
                    $iso = strtolower((string) $country['iso_code']);
                    $countries[$iso] = isset($countriesLang[$iso]) ? $countriesLang[$iso] : $countriesDefault[$iso];
                }

            }

            asort($countries);
        }

        return $countries;
    }

    /**
     * Parse HTTP_ACCEPT_LANGUAGE and get first data matching list of available languages
     *
     * @return bool|array
     *
     * @since 1.0.0
     */
    public function detectLanguage() {
        
        // This code is from a php.net comment : http://www.php.net/manual/fr/reserved.variables.server.php#94237
        $splitLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

        if (!is_array($splitLanguages)) {
            return false;
        }

        foreach ($splitLanguages as $lang) {
            $pattern = '/^(?P<primarytag>[a-zA-Z]{2,8})' .
                '(?:-(?P<subtag>[a-zA-Z]{2,8}))?(?:(?:;q=)' .
                '(?P<quantifier>\d\.\d))?$/';

            if (preg_match($pattern, $lang, $m)) {

                if (in_array($m['primarytag'], $this->getIsoList())) {
                    return $m;
                }

            }

        }

        return false;
    }

    /**
     * @param InstallLanguage $a
     * @param InstallLanguage $b
     *
     * @return int
     *
     * @since 1.0.0
     */
    protected function psUsortLanguages($a, $b) {

        $aname = $a->getMetaInformation('name');
        $bname = $b->getMetaInformation('name');

        if ($aname == $bname) {
            return 0;
        }

        return ($aname < $bname) ? -1 : 1;
    }

}
