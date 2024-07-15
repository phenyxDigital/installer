<?php

/**
 * Step 4 : configure the shop and admin access
 */
class ConfigureController extends InstallControllerHttp {

    public $listCountries = [];

    /** @var InstallSession $session */
    public $session;

    public $cacheTimezones;

    /** @var array $listActivities */
    public $listActivities;

    public $installType;

    public function __construct($step) {

        $this->step = 'configure';
        $this->step_name = $this->l('CMS information');
        parent::__construct($this->step);

    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);
        $this->addJS([
            _EPH_JS_DIR_ . 'configure.js',

        ]);

    }

    public function initContent() {

        parent::initContent();
        $this->processNextStep();
        $listActivities = [
            1  => $this->l('Lingerie and Adult'),
            2  => $this->l('Animals and Pets'),
            3  => $this->l('Art and Culture'),
            4  => $this->l('Babies'),
            5  => $this->l('Beauty and Personal Care'),
            6  => $this->l('Cars'),
            7  => $this->l('Computer Hardware and Software'),
            8  => $this->l('Download'),
            9  => $this->l('Fashion and accessories'),
            10 => $this->l('Flowers, Gifts and Crafts'),
            11 => $this->l('Food and beverage'),
            12 => $this->l('HiFi, Photo and Video'),
            13 => $this->l('Home and Garden'),
            14 => $this->l('Home Appliances'),
            15 => $this->l('Jewelry'),
            16 => $this->l('Mobile and Telecom'),
            17 => $this->l('Services'),
            18 => $this->l('Shoes and accessories'),
            19 => $this->l('Sports and Entertainment'),
            20 => $this->l('Travel'),
        ];

        asort($listActivities);
        $this->listActivities = $listActivities;

        // Countries list
        $this->listCountries = [];
        $countries = $this->language->getCountries();
        $topCountries = [
            'fr', 'es', 'it', 'de',
            'en', 'nl', 'ru',
        ];

        foreach ($topCountries as $iso) {
            $this->listCountries[] = ['iso' => $iso, 'name' => $countries[$iso]];
        }

        $this->listCountries[] = ['iso' => 0, 'name' => '-----------------'];

        foreach ($countries as $iso => $lang) {

            if (!in_array($iso, $topCountries)) {
                $this->listCountries[] = ['iso' => $iso, 'name' => $lang];
            }

        }

        // Try to detect default country

        if (!$this->session->companyCountry) {
            $detectLanguage = $this->language->detectLanguage();

            if (isset($detectLanguage['primarytag'])) {
                $this->session->companyCountry = strtolower(isset($detectLanguage['subtag']) ? $detectLanguage['subtag'] : $detectLanguage['primarytag']);
                $this->session->shopTimezone = $this->getTimezoneByIso($this->session->companyCountry);
            }

        }

        // Install type
        $this->installType = 'lite';

        $this->context->smarty->assign(
            [
                'default_iso'               => $this->session->companyCountry,
                'companyName'                  => $this->session->companyName,
                'errorcompanyName'             => $this->displayError('companyName'),
                'listActivities'            => $this->listActivities,
                'shopActivity'              => $this->session->shopActivity,
                'installType'               => $this->installType,
                'listCountries'             => $this->listCountries,
                'companyCountry'               => $this->session->companyCountry,
                'errorcompanyCountry'          => $this->displayError('companyCountry'),
                'shopTimezone'              => $this->session->shopTimezone,
                'errorShopTimezone'         => $this->displayError('shopTimezone'),
                'shopTimeeZones'            => ['us', 'ca', 'au', 'ru', 'me', 'id'],
                'timezones'                 => $this->getTimezones(),
                'shopTimezone'              => $this->session->shopTimezone,
                'adminFirstname'            => $this->session->adminFirstname,
                'errorAdminFirstname'       => $this->displayError('adminFirstname'),
                'adminLastname'             => $this->session->adminLastname,
                'errorAdminLastname'        => $this->displayError('adminLastname'),
                'adminEmail'                => $this->session->adminEmail,
                'errorAdminEmail'           => $this->displayError('adminEmail'),
                'adminPassword'             => $this->session->adminPassword,
                'errorAdminPassword'        => $this->displayError('adminPassword'),
                'adminPasswordConfirm'      => $this->session->adminPasswordConfirm,
                'errorAdminPasswordConfirm' => $this->displayError('adminPasswordConfirm'),
            ]
        );

        $this->setTemplate(_EPH_THEME_DIR_ . 'configure.tpl');

    }

    /**
     * @see InstallAbstractModel::processNextStep()
     */
    public function processNextStep() {

        if (Tools::isSubmit('companyName')) {
            // Save shop configuration
            $this->session->companyName = trim(Tools::getValue('companyName'));
            $this->session->shopActivity = Tools::getValue('shopActivity');
            $this->session->installType = Tools::getValue('dbMode');
            $this->session->companyAddress = Tools::getValue('companyAddress');
            $this->session->companyPostCode = Tools::getValue('companyPostCode');
            $this->session->companyCity = Tools::getValue('companyCity');
            $this->session->companyCountry = Tools::getValue('companyCountry');
            $this->session->shopTimezone = Tools::getValue('shopTimezone');

            // Save admin configuration
            $this->session->adminFirstname = trim(Tools::getValue('adminFirstname'));
            $this->session->adminLastname = trim(Tools::getValue('adminLastname'));
            $this->session->adminEmail = trim(Tools::getValue('adminEmail'));
            $this->session->sendInformations = Tools::getValue('sendInformations');

            // If password fields are empty, but are already stored in session, do not fill them again

            if (!$this->session->adminPassword || trim(Tools::getValue('adminPassword'))) {
                $this->session->adminPassword = trim(Tools::getValue('adminPassword'));
            }

            if (!$this->session->adminPasswordConfirm || trim(Tools::getValue('adminPasswordConfirm'))) {
                $this->session->adminPasswordConfirm = trim(Tools::getValue('adminPasswordConfirm'));
            }

        }

    }

    /**
     * @see InstallAbstractModel::validate()
     */
    public function validate() {

        // List of required fields
        $requiredFields = ['companyName', 'companyCountry', 'shopTimezone', 'adminFirstname', 'adminLastname', 'adminEmail', 'adminPassword'];

        foreach ($requiredFields as $field) {

            if (!$this->session->$field) {
                $this->errors[$field] = $this->l('Field required');
            }

        }

        // Check shop name

        if ($this->session->companyName && !Validate::isGenericName($this->session->companyName)) {
            $this->errors['companyName'] = $this->l('Invalid shop name');
        } else
        if (strlen($this->session->companyName) > 64) {
            $this->errors['companyName'] = $this->l('The field %s is limited to %d characters', $this->l('shop name'), 64);
        }

        // Check admin name

        if ($this->session->adminFirstname && !Validate::isName($this->session->adminFirstname)) {
            $this->errors['adminFirstname'] = $this->l('Your firstname contains some invalid characters');
        } else
        if (strlen($this->session->adminFirstname) > 32) {
            $this->errors['adminFirstname'] = $this->l('The field %s is limited to %d characters', $this->l('firstname'), 32);
        }

        if ($this->session->adminLastname && !Validate::isName($this->session->adminLastname)) {
            $this->errors['adminLastname'] = $this->l('Your lastname contains some invalid characters');
        } else
        if (strlen($this->session->adminLastname) > 32) {
            $this->errors['adminLastname'] = $this->l('The field %s is limited to %d characters', $this->l('lastname'), 32);
        }

        // Check passwords

        if ($this->session->adminPassword) {

            if (!Validate::isPasswdAdmin($this->session->adminPassword)) {
                $this->errors['adminPassword'] = $this->l('The password is incorrect (alphanumeric string with at least 8 characters)');
            } else
            if ($this->session->adminPassword != $this->session->adminPasswordConfirm) {
                $this->errors['adminPassword'] = $this->l('Password and its confirmation are different');
            }

        }

        // Check email

        if ($this->session->adminEmail && !Validate::isEmail($this->session->adminEmail)) {
            $this->errors['adminEmail'] = $this->l('This e-mail address is invalid');
        }

        return count($this->errors) ? false : true;
    }

    public function process() {

        if (Tools::getValue('timezoneByIso')) {
            $this->processTimezoneByIso();
        }

    }

    /**
     * Obtain the timezone associated to an iso
     */
    public function processTimezoneByIso() {

        $timezone = $this->getTimezoneByIso(Tools::getValue('iso'));
        $this->ajaxJsonAnswer(($timezone) ? true : false, $timezone);
    }

    /**
     * Get a timezone associated to an iso
     *
     * @param string $iso
     *
     * @return string
     */
    public function getTimezoneByIso($iso) {

        if (!file_exists(_EPH_INSTALL_DATA_PATH_ . 'iso_to_timezone.xml')) {
            return '';
        }

        $xml = @simplexml_load_file(_EPH_INSTALL_DATA_PATH_ . 'iso_to_timezone.xml');
        $timezones = [];

        if ($xml) {

            foreach ($xml->relation as $relation) {
                $timezones[(string) $relation['iso']] = (string) $relation['zone'];
            }

        }

        return isset($timezones[$iso]) ? $timezones[$iso] : '';
    }

    /**
     * Get list of timezones
     *
     * @return array
     */
    public function getTimezones() {

        if (!is_null($this->cacheTimezones)) {
            return [];
        }

        if (!file_exists(_EPH_INSTALL_DATA_PATH_ . 'xml/timezone.xml')) {
            return [];
        }

        $xml = @simplexml_load_file(_EPH_INSTALL_DATA_PATH_ . 'xml/timezone.xml');
        $timezones = [];

        if ($xml) {

            foreach ($xml->entities->timezone as $timezone) {
                $timezones[] = (string) $timezone['name'];
            }

        }

        return $timezones;
    }

    /**
     * Helper to display error for a field
     *
     * @param string $field
     *
     * @return string|null
     */
    public function displayError($field) {

        if (!isset($this->errors[$field])) {
            return null;
        }

        return '<span class="result aligned errorTxt">' . $this->errors[$field] . '</span>';
    }

}
