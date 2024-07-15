<?php

use CoreUpdater\CodeCallback;
use CoreUpdater\ObjectModelSchemaBuilder;

/**
 * Class InstallModelInstall
 *
 * @since 1.0.0
 */
class InstallModelInstall extends InstallAbstractModel {

    const SETTINGS_FILE = 'app/settings.inc.php';
    private static $cacheLocalizationPackContent = null;

    /**
     * @var array
     */
    public $xmlLoaderIds;

    /**
     * @var FileLogger
     */
    public $logger;

    /**
     * InstallModelInstall constructor.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PhenyxInstallerException
     */
    public function __construct() {

        parent::__construct();

        $this->logger = new FileLogger();

        if (is_writable(_EPH_ROOT_DIR_ . '/log/')) {
            $this->logger->setFilename(_EPH_ROOT_DIR_ . '/log/' . @date('Ymd') . '_installation.log');
        }

    }

   
    public function generateSettingsFile($databaseServer, $databaseLogin, $databasePassword, $databaseName, $databasePrefix) {

        // Check permissions for settings file

        if (file_exists(_EPH_WEBSITE_DIR_ . '/' . self::SETTINGS_FILE) && !is_writable(_EPH_WEBSITE_DIR_ . '/' . self::SETTINGS_FILE)) {
            $this->setError($this->language->l('%s file is not writable (check permissions)', self::SETTINGS_FILE));

            return false;
        } else if (!file_exists(_EPH_WEBSITE_DIR_ . '/' . self::SETTINGS_FILE) && !is_writable(_EPH_WEBSITE_DIR_ . '/' . dirname(self::SETTINGS_FILE))) {
            $this->setError($this->language->l('%s folder is not writable (check permissions)', dirname(self::SETTINGS_FILE)));

            return false;
        }

        // Generate settings content and write file
        $settingsConstants = [
            '_DB_SERVER_'          => $databaseServer,
            '_DB_NAME_'            => $databaseName,
            '_DB_USER_'            => $databaseLogin,
            '_DB_PASSWD_'          => $databasePassword,
            '_DB_PREFIX_'          => $databasePrefix,
            '_MYSQL_ENGINE_'       => 'InnoDB',
            '_EPH_CACHING_SYSTEM_' => 'FileBased',
            '_COOKIE_KEY_'         => Tools::passwdGen(56),
            '_COOKIE_IV_'          => Tools::passwdGen(8),
            '_EPH_CREATION_DATE_'  => date('Y-m-d'),
            '_EPH_VERSION_'        => _EPH_INSTALL_VERSION_,
            '_EPH_REVISION_'       => _EPH_INSTALL_REVISION_,
            '_EPH_BUILD_PHP_'      => _EPH_INSTALL_BUILD_PHP_,
        ];

        // If mcrypt is activated, add Rijndael 128 configuration

        if (function_exists('mcrypt_encrypt')) {
            $settingsConstants['_RIJNDAEL_KEY_'] = Tools::passwdGen(mcrypt_get_key_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC));
            $settingsConstants['_RIJNDAEL_IV_'] = base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND));
        }

        if (extension_loaded('openssl') && function_exists('openssl_encrypt')) {
            try {
                $secureKey = \Defuse\Crypto\Key::createNewRandomKey();
                $settingsConstants['_PHP_ENCRYPTION_KEY_'] = $secureKey->saveToAsciiSafeString();
            } catch (\Defuse\Crypto\Exception\EnvironmentIsBrokenException $e) {
                throw new PhenyxInstallerException("Failed to generate encryption key", 0, $e);
            }

        }

        $settingsContent = "<?php\n";

        foreach ($settingsConstants as $constant => $value) {
            $settingsContent .= "define('$constant', '" . str_replace('\'', '\\\'', $value) . "');\n";
        }

        if (!file_put_contents(_EPH_WEBSITE_DIR_ . '/' . self::SETTINGS_FILE, $settingsContent)) {
            $this->setError($this->language->l('Cannot write settings file'));

            return false;
        }

        return true;
    }

    /**
     * @param string|array $errors
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setError($errors) {

        if (!is_array($errors)) {
            $errors = [$errors];
        }

        parent::setError($errors);

        foreach ($errors as $error) {
            $this->logger->logError($error);
        }

    }

    
     public function installDatabase($clear_database = false)   {
        // Clear database (only tables with same prefix)
        require_once _EPH_WEBSITE_DIR_.'/'.self::SETTINGS_FILE;
        if ($clear_database) {
            $this->clearDatabase();
        }

        $allowed_collation = array('utf8_general_ci', 'utf8_unicode_ci');
        $collation_database = Db::getInstance()->getValue('SELECT @@collation_database');
        // Install database structure
        $sql_loader = new InstallSqlLoader();
        $sql_loader->setMetaData(array(
            'PREFIX_' => _DB_PREFIX_,
            'ENGINE_TYPE' => _MYSQL_ENGINE_,
            'COLLATION' => (empty($collation_database) || !in_array($collation_database, $allowed_collation)) ? '' : 'COLLATE '.$collation_database
        ));

        try {
            $sql_loader->parseFile(_EPH_INSTALL_DATA_PATH_.'db_structure.sql');
        } catch (PhenyxInstallerException $e) {
            $this->setError($this->language->l('Database structure file not found'));
            return false;
        }

        if ($errors = $sql_loader->getErrors()) {
            foreach ($errors as $error) {
                $this->setError($this->language->l('SQL error on query <i>%s</i>', $error['error']));
            }
            return false;
        }

        return true;
    }
    /**
     * Clear database (only tables with same prefix).
     *
     * @param Db $conn
     * @throws PhenyxDatabaseException
     * @throws PhenyxException
     *
     * @version 1.0.0 Initial version, $truncate deprecated.
     * @version 1.1.0 Removed $truncate.
     */
    public function clearDatabase($conn) {

        foreach ($conn->executeS('SHOW TABLES') as $row) {
            $table = current($row);

            if (!_DB_PREFIX_ || preg_match('#^' . _DB_PREFIX_ . '#i', $table)) {
                $conn->execute(('DROP TABLE') . ' `' . $table . '`');
            }

        }

    }

    /**
     * PROCESS : installDefaultData
     * Create default shop and languages
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param string $companyName
     * @param int|bool $isoCountry
     * @param bool $allLanguages
     * @param bool $clearDatabase
     *
     * @return bool
     * @throws PhenyxException
     */
    public function installDefaultData($session, $allLanguages = false, $clearDatabase = false) {

        //require_once _EPH_WEBSITE_DIR_.'/config.inc.php';
        // Install first shop
        $isoCountry = $session->companyCountry;
        $companyName = $session->companyName;

        if (!$this->createCompany($session)) {
            return false;
        }

        // Install languages
        try {

            if (!$allLanguages) {
                $isoCodesToInstall = [$this->language->getLanguageIso()];

                if ($isoCountry) {
                    $version = str_replace('.', '', _EPH_VERSION_);
                    $version = substr($version, 0, 2);
                    $localizationFileContent = $this->getLocalizationPackContent($version, $isoCountry);

                    if ($xml = @simplexml_load_string($localizationFileContent)) {

                        foreach ($xml->languages->language as $language) {
                            $isoCodesToInstall[] = (string) $language->attributes()->iso_code;
                        }

                    }

                }

            } else {
                $isoCodesToInstall = null;
            }

            $isoCodesToInstall = array_flip(array_flip($isoCodesToInstall));
            $languages = $this->installLanguages($isoCodesToInstall);
        } catch (PhenyxInstallerException $e) {
            $this->setError($e->getMessage());

            return false;
        }

        $flipLanguages = array_flip($languages);
        $idLang = (!empty($flipLanguages[$this->language->getLanguageIso()])) ? $flipLanguages[$this->language->getLanguageIso()] : 1;
        Configuration::updateGlobalValue('EPH_LANG_DEFAULT', $idLang);
        Configuration::updateGlobalValue('EPH_VERSION_DB', _EPH_INSTALL_VERSION_);
        Configuration::updateGlobalValue('EPH_INSTALL_VERSION', _EPH_INSTALL_VERSION_);

        return true;
    }

    /**
     * @param string $companyName
     *
     * @return bool
     *
     * @throws PhenyxDatabaseException
     * @throws PhenyxException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function createCompany($session) {
        
        if ($iso) {
            $id_country = Country::getByIso($session->companyCountry);
                    
        } else {
            return false;
        }

        // Create default shop
        $company = new Company();
        $company->id_country_registration = $id_country;
        $company->active = true;
        $company->id_category = 2;
        $company->id_theme = 1;
        $company->company = $session->companyName;
        $company->company_name = $session->companyName;
        $company->company_url = Tools::getHttpHost();
        $company->company_email = $session->adminEmail;
        $company->lastname = $session->adminLastname;
        $company->firstname = $session->firstname;
        $company->address1 = $session->companyAddress;
        $company->postcode = $session->companyPostCode;
        $company->city = $session->companyCity;

        if (!$company->add()) {
            $this->setError($this->language->l('Cannot create company') . ' / ' . Db::getInstance()->getMsgError());

            return false;
        }

        Context::getContext()->company = $company;

        // Create default shop URL
        $shopUrl = new CompanyUrl();
        $shopUrl->domain = Tools::getHttpHost();
        $shopUrl->domain_ssl = Tools::getHttpHost();
        $shopUrl->physical_uri = __EPH_BASE_URI__;
        $shopUrl->id_company = $company->id;
        $shopUrl->main = true;
        $shopUrl->active = true;

        if (!$shopUrl->add()) {
            $this->setError($this->language->l('Cannot create compant URL') . ' / ' . Db::getInstance()->getMsgError());

            return false;
        }

        return true;
    }

    /**
     * @param string $version
     * @param string $country
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLocalizationPackContent($version, $country) {

        if (InstallModelInstall::$cacheLocalizationPackContent === null || array_key_exists($country, InstallModelInstall::$cacheLocalizationPackContent)) {
            $pathCacheFile = _EPH_WEBSITE_DIR_ . 'app/cache/sandbox' . DIRECTORY_SEPARATOR . $version . $country . '.xml';

            $localizationFile = _EPH_WEBSITE_DIR_ . '/content/localization/default.xml';

            if (file_exists(_EPH_WEBSITE_DIR_. '/content/localization/' . $country . '.xml')) {
                $localizationFile = _EPH_WEBSITE_DIR_ . '/content/localization/' . $country . '.xml';
            }

            $localizationFileContent = file_get_contents($localizationFile);

            file_put_contents($pathCacheFile, $localizationFileContent);

            InstallModelInstall::$cacheLocalizationPackContent[$country] = $localizationFileContent;
        }

        return isset(InstallModelInstall::$cacheLocalizationPackContent[$country]) ? InstallModelInstall::$cacheLocalizationPackContent[$country] : false;
    }

    /**
     * Install languages
     *
     * @return array Association between ID and iso array(id_lang => iso, ...)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function installLanguages($languagesList = null) {

        if ($languagesList == null || !is_array($languagesList) || !count($languagesList)) {
            $languagesList = $this->language->getIsoList();
        }

        $languagesAvailable = $this->language->getIsoList();
        $languages = [];

        foreach ($languagesList as $iso) {

            if (!in_array($iso, $languagesAvailable)) {
                continue;
            }

            if (!file_exists(_EPH_INSTALL_LANGS_PATH_ . $iso . '/language.xml')) {
                throw new PhenyxInstallerException($this->language->l('File "language.xml" not found for language iso "%s"', $iso));
            }

            if (!$xml = @simplexml_load_file(_EPH_INSTALL_LANGS_PATH_ . $iso . '/language.xml')) {
                throw new PhenyxInstallerException($this->language->l('File "language.xml" not valid for language iso "%s"', $iso));
            }

            $paramsLang = [
                'name'                     => (string) $xml->name,
                'iso_code'                 => substr((string) $xml->language_code, 0, 2),
                'language_code'            => (string) $xml->language_code,
                'allow_accented_chars_url' => (string) $xml->allow_accented_chars_url,
            ];

            $errors = Language::downloadAndInstallLanguagePack($iso, _EPH_INSTALL_VERSION_, $paramsLang);

            if (is_array($errors)) {
                $installed = false;
                $name = ($xml->name) ? $xml->name : $iso;

                $this->setError($this->language->l('Translations for %s and ephenyx digital version %s not found.', $name, _EPH_INSTALL_VERSION_));
                $this->setError($errors);

                $version = array_map('intval', explode('.', _EPH_INSTALL_VERSION_, 3));

                if (isset($version[2]) && $version[2] > 0) {
                    $version[2]--;
                    $version = implode('.', $version);

                    $errors = Language::downloadAndInstallLanguagePack($iso, $version, $paramsLang);

                    if (is_array($errors)) {
                        $this->setError($this->language->l('Translations for ephenyx digital version %s not found either.', $version));
                        $this->setError($errors);
                    } else {
                        $installed = true;
                        $this->setError($this->language->l('Installed translations for ephenyx digital version %s instead.', $version));
                    }

                }

                if (!$installed) {
                    $this->setError($this->language->l('Translations for %s not installed. You can catch up on this later.', $name));

                    // XML is actually (almost) a language pack.
                    $xml->name = (string) $xml->name;
                    $xml->is_rtl = filter_var($xml->is_rtl, FILTER_VALIDATE_BOOLEAN);

                    Language::checkAndAddLanguage($iso, $xml, true, $paramsLang);
                }

            }

            Language::loadLanguages();
            Tools::clearCache();

            if (!$idLang = Language::getIdByIso($iso, true)) {
                throw new PhenyxInstallerException($this->language->l('Cannot install language "%s"', ($xml->name) ? $xml->name : $iso));
            }

            $languages[$idLang] = $iso;

            // Copy language flag

            if (is_writable(_EPH_IMG_DIR_ . 'l/')) {

                if (!copy(_EPH_INSTALL_LANGS_PATH_ . $iso . '/flag.jpg', _EPH_IMG_DIR_ . 'l/' . $idLang . '.jpg')) {
                    throw new PhenyxInstallerException($this->language->l('Cannot copy flag language "%s"', _EPH_INSTALL_LANGS_PATH_ . $iso . '/flag.jpg => ' . _EPH_IMG_DIR_ . 'l/' . $idLang . '.jpg'));
                }

            }

        }

        return $languages;
    }

    /**
     * PROCESS : populateDatabase
     * Populate database with default data
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param null $entity
     *
     * @return bool
     */
    public function populateDatabase($entity = null) {

        $languages = [];

        foreach (Language::getLanguages(true) as $lang) {
            $languages[$lang['id_lang']] = $lang['iso_code'];
        }

        // Install XML data (data/xml/ folder)
        $xmlLoader = new InstallXmlLoader();
        $xmlLoader->setLanguages($languages);

        if (isset($this->xmlLoaderIds) && $this->xmlLoaderIds) {
            $xmlLoader->setIds($this->xmlLoaderIds);
        }

        if ($entity) {

            if (is_array($entity)) {

                foreach ($entity as $item) {
                    $xmlLoader->populateEntity($item);
                }

            } else {
                $xmlLoader->populateEntity($entity);
            }

        } else {
            $xmlLoader->populateFromXmlFiles();
        }

        if ($errors = $xmlLoader->getErrors()) {
            $this->setError($errors);

            return false;
        }

        // IDS from xmlLoader are stored in order to use them for fixtures
        $this->xmlLoaderIds = $xmlLoader->getIds();
        unset($xmlLoader);

        // Install custom SQL data (db_data.sql file)

        if (file_exists(_EPH_INSTALL_DATA_PATH_ . 'db_data.sql')) {
            $sqlLoader = new InstallSqlLoader();
            $sqlLoader->setMetaData(
                [
                    'PREFIX_'     => _DB_PREFIX_,
                    'ENGINE_TYPE' => _MYSQL_ENGINE_,
                ]
            );

            $sqlLoader->parseFile(_EPH_INSTALL_DATA_PATH_ . 'db_data.sql', false);

            if ($errors = $sqlLoader->getErrors()) {
                $this->setError($errors);

                return false;
            }

        }

        // Copy language default images (we do this action after database in populated because we need image types information)

        foreach ($languages as $iso) {
            $this->copyLanguageImages($iso);
        }

        return true;
    }

    /**
     * @param $iso
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function copyLanguageImages($iso) {

        $imgPath = _EPH_INSTALL_LANGS_PATH_ . $iso . '/img/';

        if (!is_dir($imgPath)) {
            return;
        }

        $list = [
            'products'      => _EPH_PROD_IMG_DIR_,
            'categories'    => _EPH_CAT_IMG_DIR_,
            'manufacturers' => _EPH_MANU_IMG_DIR_,
            'suppliers'     => _EPH_SUPP_IMG_DIR_,
            'scenes'        => _EPH_SCENE_IMG_DIR_,
            'stores'        => _EPH_STORE_IMG_DIR_,
            null            => _EPH_IMG_DIR_ . 'l/', // Little trick to copy images in img/l/ path with all types
        ];

        foreach ($list as $cat => $dstPath) {

            if (!is_writable($dstPath)) {
                continue;
            }

            if (file_exists(_EPH_IMG_DIR_ . "/flags/$iso.png")) {
                $src = _EPH_IMG_DIR_ . "/flags/$iso.png";
            } else {
                $src = _EPH_INSTALL_LANGS_PATH_ . "$iso/flag.jpg";
            }

            copy($src, $dstPath . $iso . '.jpg');

            $types = ImageType::getImagesTypes($cat);

            foreach ($types as $type) {

                if (file_exists($imgPath . $iso . '-default-' . $type['name'] . '.jpg')) {
                    copy($imgPath . $iso . '-default-' . $type['name'] . '.jpg', $dstPath . $iso . '-default-' . $type['name'] . '.jpg');
                } else {
                    ImageManager::resize($imgPath . $iso . '.jpg', $dstPath . $iso . '-default-' . $type['name'] . '.jpg', $type['width'], $type['height']);
                }

            }

        }

    }

    /**
     * PROCESS : configureShop
     * Set default shop configuration
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function configureShop(array $data = []) {

        //clear image cache in tmp folder

        if (file_exists(_EPH_TMP_IMG_DIR_)) {

            foreach (scandir(_EPH_TMP_IMG_DIR_) as $file) {

                if ($file[0] != '.' && $file != 'index.php') {
                    Tools::deleteFile(_EPH_TMP_IMG_DIR_ . $file);
                }

            }

        }

        $defaultData = [
            'companyName'       => 'My Company',
            'shopActivity'   => '',
            'companyCountry'    => 'us',
            'shopTimezone'   => 'US/Eastern',
            'useSmtp'        => false,
            'smtpEncryption' => 'off',
            'smtpPort'       => 25,
            'rewriteEngine'  => false,
        ];

        foreach ($defaultData as $k => $v) {

            if (!isset($data[$k])) {
                $data[$k] = $v;
            }

        }

        Context::getContext()->company = new Company(1);
        Configuration::loadConfiguration();

        $idCountry = (int) Country::getByIso($data['companyCountry']);

        // Set default configuration
        Configuration::updateGlobalValue('EPH_SHOP_DOMAIN', Tools::getHttpHost());
        Configuration::updateGlobalValue('EPH_SHOP_DOMAIN_SSL', Tools::getHttpHost());
        Configuration::updateGlobalValue('EPH_INSTALL_VERSION', _EPH_INSTALL_VERSION_);
        Configuration::updateGlobalValue('EPH_LOCALE_LANGUAGE', $this->language->getLanguageIso());
        Configuration::updateGlobalValue('EPH_SHOP_NAME', $data['companyName']);
        Configuration::updateGlobalValue('EPH_SHOP_ACTIVITY', $data['shopActivity']);
        Configuration::updateGlobalValue('EPH_COUNTRY_DEFAULT', $idCountry);
        Configuration::updateGlobalValue('EPH_LOCALE_COUNTRY', $data['companyCountry']);
        Configuration::updateGlobalValue('EPH_TIMEZONE', $data['shopTimezone']);
        Configuration::updateGlobalValue('EPH_CONFIGURATION_AGREMENT', (int) $data['configurationAgreement']);

        // Set mails configuration
        Configuration::updateGlobalValue('EPH_MAIL_METHOD', ($data['useSmtp']) ? Mail::MAIL_METHOD_SMTP : Mail::MAIL_METHOD_MAIL);
        Configuration::updateGlobalValue('EPH_MAIL_SMTP_ENCRYPTION', $data['smtpEncryption']);
        Configuration::updateGlobalValue('EPH_MAIL_SMTP_PORT', $data['smtpPort']);

        // Set default rewriting settings
        Configuration::updateGlobalValue('EPH_REWRITING_SETTINGS', $data['rewriteEngine']);

        // Choose the best ciphering algorithm available
        Configuration::updateGlobalValue('EPH_CIPHER_ALGORITHM', $this->getCipherAlgorightm());

        $groups = Group::getGroups((int) Configuration::get('EPH_LANG_DEFAULT'));
        $groupsDefault = Db::getInstance()->executeS('SELECT `name` FROM ' . _DB_PREFIX_ . 'configuration WHERE `name` LIKE "EPH_%_GROUP" ORDER BY `id_configuration`');

        foreach ($groupsDefault as &$groupDefault) {

            if (is_array($groupDefault) && isset($groupDefault['name'])) {
                $groupDefault = $groupDefault['name'];
            }

        }

        if (is_array($groups) && count($groups)) {

            foreach ($groups as $key => $group) {

                if (Configuration::get($groupsDefault[$key]) != $groups[$key]['id_group']) {
                    Configuration::updateGlobalValue($groupsDefault[$key], (int) $groups[$key]['id_group']);
                }

            }

        }

        $states = Db::getInstance()->executeS('SELECT `id_order_state` FROM ' . _DB_PREFIX_ . 'order_state ORDER BY `id_order_state`');
        $statesDefault = Db::getInstance()->executeS('SELECT MIN(`id_configuration`), `name` FROM ' . _DB_PREFIX_ . 'configuration WHERE `name` LIKE "EPH_OS_%" GROUP BY `value` ORDER BY`id_configuration`');

        foreach ($statesDefault as &$stateDefault) {

            if (is_array($stateDefault) && isset($stateDefault['name'])) {
                $stateDefault = $stateDefault['name'];
            }

        }

        if (is_array($states) && count($states)) {

            foreach ($states as $key => $state) {

                if (Configuration::get($statesDefault[$key]) != $states[$key]['id_order_state']) {
                    Configuration::updateGlobalValue($statesDefault[$key], (int) $states[$key]['id_order_state']);
                }

            }

            /* deprecated order state */
            Configuration::updateGlobalValue('EPH_OS_OUTOFSTOCK_PAID', (int) Configuration::get('EPH_OS_OUTOFSTOCK'));
        }

        // Set logo configuration

        if (file_exists(_EPH_IMG_DIR_ . 'logo.jpg')) {
            list($width, $height) = getimagesize(_EPH_IMG_DIR_ . 'logo.jpg');
            Configuration::updateGlobalValue('SHOP_LOGO_WIDTH', round($width));
            Configuration::updateGlobalValue('SHOP_LOGO_HEIGHT', round($height));
        }

        Configuration::updateGlobalValue('EPH_SMARTY_CACHE', 1);

        // Active only the country selected by the merchant
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'country SET active = 0 WHERE id_country != ' . (int) $idCountry);

        // Set localization configuration
        $version = str_replace('.', '', _EPH_VERSION_);
        $version = substr($version, 0, 2);
        $localizationFileContent = $this->getLocalizationPackContent($version, $data['companyCountry']);

        $locale = new LocalizationPackCore();
        $locale->loadLocalisationPack($localizationFileContent, '', true);

        // Create default employee

        if (isset($data['adminFirstname']) && isset($data['adminLastname']) && isset($data['adminPassword']) && isset($data['adminEmail'])) {
            $employee = new Employee();
            $employee->is_admin = true;
            $employee->firstname = Tools::ucfirst($data['adminFirstname']);
            $employee->lastname = Tools::ucfirst($data['adminLastname']);
            $employee->email = $data['adminEmail'];
            $employee->passwd = Tools::hash($data['adminPassword']);
            $employee->password = $data['adminPassword'];
            $employee->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-360 minutes'));
            $employee->bo_theme = 'blacktie';
            $employee->active = true;
            $employee->optin = true;
            $employee->id_profile = 1;
            $employee->id_lang = Configuration::get('EPH_LANG_DEFAULT');

            if (!$employee->add()) {
                $this->setError($this->language->l('Cannot create admin account'));

                return false;
            }

        } else {
            $this->setError($this->language->l('Cannot create admin account'));

            return false;
        }

        // Update default contact

        if (isset($data['adminEmail'])) {
            Configuration::updateGlobalValue('EPH_SHOP_EMAIL', $data['adminEmail']);

            $contacts = new PhenyxCollection('Contact');

            foreach ($contacts as $contact) {
                /** @var Contact $contact */
                $contact->email = $data['adminEmail'];
                $contact->update();
            }

        }

        if (!@Tools::generateHtaccess(null, $data['rewriteEngine'])) {
            Configuration::updateGlobalValue('EPH_REWRITING_SETTINGS', 0);
        }

        return true;
    }

    /**
     * PROCESS : installPlugins
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param null $module
     *
     * @return bool
     */
    public function installPlugins($module = null) {

        if ($module && !is_array($module)) {
            $module = [$module];
        }

        $modules = $module ? $module : $this->getPluginsList();

        Plugin::updateTranslationsAfterInstall(false);

        $errors = [];

        foreach ($modules as $moduleName) {

            if (!file_exists(_EPH_MODULE_DIR_ . $moduleName . '/' . $moduleName . '.php')) {
                continue;
            }

            $module = Plugin::getInstanceByName($moduleName);

            if (!$module->install()) {
                $errors[] = $this->language->l('Cannot install module "%s"', $moduleName);
            }

        }

        if ($errors) {
            $this->setError($errors);

            return false;
        }

        Plugin::updateTranslationsAfterInstall(true);
        Language::updatePluginsTranslations($modules);

        return true;
    }

    /**
     * @return array List of modules to install.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @version 1.0.6 Move the hardcoded list to default_modules.php.
     */
    public function getPluginsList() {

        global $_EPH_DEFAULT_PLUGINS_;

        return $_EPH_DEFAULT_PLUGINS_;
    }

    /**
     * PROCESS : installFixtures
     * Install fixtures (E.g. demo products)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param null  $entity
     * @param array $data
     *
     * @return bool
     */
    public function installFixtures($entity = null, array $data = []) {

        $fixturesPath = _EPH_INSTALL_FIXTURES_PATH_ . 'thirtybees/';
        $fixturesName = 'thirtybees';

        // Load class (use fixture class if one exists, or use InstallXmlLoader)

        if (file_exists($fixturesPath . '/install.php')) {
            require_once $fixturesPath . '/install.php';
            $class = 'InstallFixtures' . Tools::toCamelCase($fixturesName);

            if (!class_exists($class, false)) {
                $this->setError($this->language->l('Fixtures class "%s" not found', $class));

                return false;
            }

            $xmlLoader = new $class();

            if (!$xmlLoader instanceof InstallXmlLoader) {
                $this->setError($this->language->l('"%s" must be an instance of "InstallXmlLoader"', $class));

                return false;
            }

        } else {
            $xmlLoader = new InstallXmlLoader();
        }

        $languages = [];

        foreach (Language::getLanguages(false) as $lang) {
            $languages[$lang['id_lang']] = $lang['iso_code'];
        }

        $xmlLoader->setLanguages($languages);

        // Install XML data (data/xml/ folder)

        if (isset($this->xmlLoaderIds) && $this->xmlLoaderIds) {
            $xmlLoader->setIds($this->xmlLoaderIds);
        } else {
            // Load from default path, stuff for populateDatabase().
            $xmlLoader->populateFromXmlFiles(false);
        }

        // Switch to fixtures path.
        $xmlLoader->setFixturesPath($fixturesPath);

        if ($entity) {

            if (is_array($entity)) {

                foreach ($entity as $item) {
                    $xmlLoader->populateEntity($item);
                }

            } else {
                $xmlLoader->populateEntity($entity);
            }

        } else {
            $xmlLoader->populateFromXmlFiles();
        }

        if ($errors = $xmlLoader->getErrors()) {
            $this->setError($errors);

            return false;
        }

        // Store IDs for the next run of this method.
        $this->xmlLoaderIds = $xmlLoader->getIds();
        unset($xmlLoader);

        // Index products in search tables
        Search::indexation(true);

        return true;
    }

    /**
     * PROCESS : initializeClasses
     *
     * Executes initialization callbacks on all classes that implements the interface
     *
     * @return bool
     */
    public function initializeClasses() {

        static::loadCoreUpdater();
        try {
            $callback = new CodeCallback();
            $callback->execute(Db::getInstance());
            return true;
        } catch (Exception $e) {
            $this->setError($this->language->l('Failed to initialize classes: %s', $e->getMessage()));
            return false;

        }

    }

    /**
     * PROCESS : installTheme
     * Install theme
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function installTheme() {

        $theme = Theme::installFromDir(_EPH_ALL_THEMES_DIR_ . _THEME_NAME_);

        if (Validate::isLoadedObject($theme)) {
            // Never returns an error.
            $theme->installIntoShopContext();
        } else {
            $this->setError($this->language->l('Failed to import theme.'));
            $this->setError($theme);

            return false;
        }

        // Override some module defaults to fit the default theme.
        $sqlLoader = new InstallSqlLoader();
        $sqlLoader->setMetaData(
            [
                'PREFIX_'     => _DB_PREFIX_,
                'ENGINE_TYPE' => _MYSQL_ENGINE_,
            ]
        );

        $sqlLoader->parseFile(_EPH_INSTALL_DATA_PATH_ . 'theme.sql', false);

        if ($errors = $sqlLoader->getErrors()) {
            $this->setError($errors);

            return false;
        }

        return true;
    }

    /**
     * Returns best ciphering algorithm available for current environment
     *
     * @since   1.0.7
     * @version 1.0.7 Initial version
     * @deprecated 1.1.0 Introduced for working around a broken Cloudways
     *                   distribution, only. Plan for 1.1.0 is to remove all
     *                   but one encryption algorithms. Also to remove the
     *                   direct dependency on paragonie/random_compat, which
     *                   was introduced for the same reason.
     */
    public function getCipherAlgorightm() {

        // use PhpEncryption if openssl is enabled

        if (extension_loaded('openssl') && function_exists('openssl_encrypt')) {
            return 2;
        }

        // use RIJNDAEL if mcrypt is enabled, and we are not on php7

        if (extension_loaded('mcrypt') && function_exists('mcrypt_encrypt') && PHP_VERSION_ID < 70100) {
            return 1;
        }

        // fallback - use Blowfish php implementation
        return 0;
    }

    /**
     * Includes core updater classes
     */
    protected static function loadCoreUpdater() {

        $dir = _EPH_MODULE_DIR_ . 'coreupdater/';

        if (!file_exists($dir)) {
            throw new RuntimeException('Core updater is not part of the installation package!');
        }

        require_once $dir . 'classes/schema/autoload.php';
        require_once $dir . 'classes/CodeCallback.php';
    }

}
