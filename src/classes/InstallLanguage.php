<?php

/**
 * Class InstallLanguage
 *
 * @since 1.0.0
 */
class InstallLanguage {

    /**
     * @var string Current language folder
     */
    protected $path;

    /**
     * @var string Current language iso
     */
    public $iso;

    /**
     * @var array Cache list of installer translations for this language
     */
    protected $data;

    protected $fixtures_data;

    /**
     * @var array Cache list of informations in language.xml file
     */
    protected $meta;

    /**
     * @var array Cache list of countries for this language
     */
    protected $countries;
    
    public $name;

    /**
     * InstallLanguage constructor.
     *
     * @param string $iso
     *
     * @since 1.0.0
     */
    public function __construct($iso) {

        $this->path = _EPH_INSTALL_LANGS_PATH_ . $iso . '/';
        $this->iso = $iso;
        $this->name = $this->getMetaInformation('name');
    }

    /**
     * Get iso for current language
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getIso() {

        return $this->iso;
    }

    /**
     * Get an information from language.xml file (E.g. $this->getMetaInformation('name'))
     *
     * @param string $key
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getMetaInformation($key) {

        if (!is_array($this->meta)) {
            $this->meta = [];
            $xml = @simplexml_load_file($this->path . 'language.xml');

            if ($xml) {

                foreach ($xml->children() as $node) {
                    $this->meta[$node->getName()] = (string) $node;
                }

            }

        }

        return isset($this->meta[$key]) ? $this->meta[$key] : null;
    }

    /**
     * @param string $key
     * @param string $type
     *
     * @return null
     *
     * @since 1.0.0
     */
    public function getTranslation($key, $type = 'translations') {

        if (!is_array($this->data)) {
            $this->data = file_exists($this->path . 'install.php') ? include $this->path . 'install.php' : [];
        }

        return isset($this->data[$type][$key]) ? $this->data[$type][$key] : null;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getCountries() {

        if (!is_array($this->countries)) {
            $this->countries = [];

            if (file_exists($this->path . 'data/country.xml')) {

                if ($xml = @simplexml_load_file($this->path . 'data/country.xml')) {

                    foreach ($xml->country as $country) {
                        $this->countries[strtolower((string) $country['id'])] = (string) $country->name;
                    }

                }

            }

        }

        return $this->countries;
    }

}
