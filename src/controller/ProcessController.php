<?php

/**
 * Class InstallControllerHttpProcess
 *
 * @since 1.0.0
 */
class ProcessController extends InstallControllerHttp {

    const SETTINGS_FILE = 'app/settings.inc.php';

    public $processSteps = [];

    public $previousButton = false;

    /** @var InstallModelInstall $modelInstall */
    public $modelInstall;

    /** @var InstallSession $session */
    public $session;

    public function __construct($step) {

        $this->step = 'process';
        $this->step_name = $this->l('CMS installation');
        parent::__construct($this->step);

    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);
        $this->addJS([
            _EPH_JS_DIR_ . 'process.js',

        ]);

    }

    /**
     * @since 1.0.0
     */
    public function init() {

        $this->modelInstall = new InstallModelInstall();
    }

    /**
     * @since 1.0.0
     */
    public function processNextStep() {}

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate() {

        return false;
    }

    /**
     * @since 1.0.0
     *
     * @throws PhenyxException
     */
    public function process() {

        if (file_exists(_EPH_WEBSITE_DIR_ . '/' . self::SETTINGS_FILE)) {
            require_once _EPH_WEBSITE_DIR_ . '/' . self::SETTINGS_FILE;
        }

        if (!$this->session->processValidated) {
            $this->session->processValidated = [];
        }

        if (Tools::getValue('generateSettingsFile')) {
            $this->processGenerateSettingsFile();
        } else
        if (Tools::getValue('installDatabase') && !empty($this->session->processValidated['generateSettingsFile'])) {
            $this->processInstallDatabase();
        } else
        if (Tools::getValue('installDefaultData')) {
            $this->processInstallDefaultData();
        } else
        if (Tools::getValue('populateDatabase') && !empty($this->session->processValidated['installDatabase'])) {
            $this->processPopulateDatabase();
        } else
        if (Tools::getValue('configureShop') && !empty($this->session->processValidated['populateDatabase'])) {
            $this->processConfigureShop();
        } else
        if (Tools::getValue('installFixtures') && !empty($this->session->processValidated['configureShop'])) {
            $this->processInstallFixtures();
        } else
        if (Tools::getValue('initializeClasses') && !empty($this->session->processValidated['configureShop'])) {
            $this->processInitializeClasses();
        } else
        if (Tools::getValue('installPlugins') && (!empty($this->session->processValidated['installFixtures']) || $this->session->installType != 'full')) {
            unset($_SESSION['xmlLoaderIds']);
            $this->processInstallPlugins();
        } else
        if (Tools::getValue('installTheme')) {
            $this->processInstallTheme();
        } else {
            // With no parameters, we consider that we are doing a new install, so session where the last process step
            // was stored can be cleaned

            if (Tools::getValue('restart')) {
                $this->session->processValidated = [];
                $this->session->databaseClear = true;
            } else
            if (!Tools::getValue('submitNext')) {
                $this->session->step = 'configure';
                $this->session->lastStep = 'configure';
                Tools::redirect('index.php');
            }

        }

    }

    /**
     * PROCESS : generateSettingsFile
     *
     * @since 1.0.0
     */
    public function processGenerateSettingsFile() {

        $success = $this->modelInstall->generateSettingsFile(
            $this->session->databaseServer,
            $this->session->databaseLogin,
            $this->session->databasePassword,
            $this->session->databaseName,
            $this->session->databasePrefix
        );

        if (!$success) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }

        $this->session->processValidated = array_merge($this->session->processValidated, ['generateSettingsFile' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * PROCESS : installDatabase
     * Create database structure
     * @throws PhenyxException
     */
    public function processInstallDatabase() {

        if (!$this->modelInstall->installDatabase($this->session->databaseClear)) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }

        $this->session->processValidated = array_merge($this->session->processValidated, ['installDatabase' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * PROCESS : installDefaultData
     * Create default shop and languages
     */
    public function processInstallDefaultData() {

        $result = $this->modelInstall->installDefaultData($this->session, false, true);

        if (!$result) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }

        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * PROCESS : populateDatabase
     * Populate database with default data
     *
     * @throws PhenyxException
     */
    public function processPopulateDatabase() {

        $this->initializeContext();

        $this->modelInstall->xmlLoaderIds = $this->session->xmlLoaderIds;
        $result = $this->modelInstall->populateDatabase(Tools::getValue('entity'));

        if (!$result) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }

        $this->session->xmlLoaderIds = $this->modelInstall->xmlLoaderIds;
        $this->session->processValidated = array_merge($this->session->processValidated, ['populateDatabase' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * Initialize context
     *
     * @throws PhenyxException
     */
    public function initializeContext() {

        Context::getContext()->shop = new Shop(1);
        Shop::setContext(Shop::CONTEXT_SHOP, 1);
        Configuration::loadConfiguration();
        Context::getContext()->language = new Language(Configuration::get('EPH_LANG_DEFAULT'));
        Context::getContext()->country = new Country(Configuration::get('EPH_COUNTRY_DEFAULT'));
        Context::getContext()->currency = new Currency(Configuration::get('EPH_CURRENCY_DEFAULT'));
        Context::getContext()->cart = new Cart();
        Context::getContext()->employee = new Employee(1);
        $protocol = (Tools::usingSecureMode() && Configuration::get('EPH_SSL_ENABLED')) ? 'https://' : 'http://';
        Context::getContext()->link = new Link($protocol, $protocol);
        Context::getContext()->smarty = require_once _EPH_ROOT_DIR_ . '/config/smarty.config.inc.php';
    }

    /**
     * PROCESS : configureShop
     * Set default shop configuration
     *
     * @throws PhenyxException
     */
    public function processConfigureShop() {

        $this->initializeContext();

        $success = $this->modelInstall->configureShop(
            [
                'companyName'               => $this->session->companyName,
                'shopActivity'           => $this->session->shopActivity,
                'companyCountry'            => $this->session->companyCountry,
                'shopTimezone'           => $this->session->shopTimezone,
                'adminFirstname'         => $this->session->adminFirstname,
                'adminLastname'          => $this->session->adminLastname,
                'adminPassword'          => $this->session->adminPassword,
                'adminEmail'             => $this->session->adminEmail,
                'sendInformations'       => $this->session->sendInformations,
                'configurationAgreement' => $this->session->configurationAgreement,
                'rewriteEngine'          => $this->session->rewriteEngine,
            ]
        );

        if (!$success) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }

        $this->session->processValidated = array_merge($this->session->processValidated, ['configureShop' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * PROCESS : installFixtures
     * Install fixtures (E.g. demo products)
     *
     * @throws PhenyxException
     */
    public function processInstallFixtures() {

        $this->initializeContext();

        $this->modelInstall->xmlLoaderIds = $this->session->xmlLoaderIds;

        if (!$this->modelInstall->installFixtures(Tools::getValue('entity', null), ['shopActivity' => $this->session->shopActivity, 'companyCountry' => $this->session->companyCountry])) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }

        $this->session->xmlLoaderIds = $this->modelInstall->xmlLoaderIds;
        $this->session->processValidated = array_merge($this->session->processValidated, ['installFixtures' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * PROCESS : initializeClasses
     * Executes initialization callbacks on all classes that implements the interface
     *
     * @throws PhenyxException
     */
    public function processInitializeClasses() {

        $this->initializeContext();

        if ($this->modelInstall->initializeClasses()) {
            $this->session->processValidated = array_merge($this->session->processValidated, ['initializeClasses' => true]);
            $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
        } else {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }

    }

    /**
     * PROCESS : installPlugins
     * Install all modules in ~/modules/ directory
     *
     * @throws PhenyxException
     */
    public function processInstallPlugins() {

        $this->initializeContext();

        $result = $this->modelInstall->installPlugins(Tools::getValue('module'));

        if (!$result) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }

        $this->session->processValidated = array_merge($this->session->processValidated, ['installPlugins' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * PROCESS : installTheme
     * Install theme
     *
     * @throws PhenyxException
     */
    public function processInstallTheme() {

        $this->initializeContext();

        $result = $this->modelInstall->installTheme();

        if (!$result) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }

        $this->session->processValidated = array_merge($this->session->processValidated, ['installTheme' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    public function initContent() {

        parent::initContent();
        $this->process();
        $this->processSteps[] = [
            'key'  => 'generateSettingsFile',
            'lang' => $this->l('Create settings.inc file'),
        ];

        $this->processSteps[] = [
            'key'  => 'installDatabase',
            'lang' => $this->l('Create database tables'),
        ];

        $this->processSteps[] = [
            'key'  => 'installDefaultData',
            'lang' => $this->l('Create default website and languages'),
        ];

        $populateStep = [
            'key'      => 'populateDatabase',
            'lang'     => $this->l('Populate database tables'),
            'subtasks' => [],
        ];
        $xmlLoader = new InstallXmlLoader();

        foreach (array_chunk($xmlLoader->getSortedEntities(), 10) as $entity) {
            $populateStep['subtasks'][] = ['entity' => $entity];
        }

        $this->processSteps[] = $populateStep;

        $this->processSteps[] = [
            'key'  => 'configureShop',
            'lang' => $this->l('Configure website information'),
        ];

        if ($this->session->installType == 'full') {
            $fixturesStep = [
                'key'      => 'installFixtures',
                'lang'     => $this->l('Install demonstration data'),
                'subtasks' => [],
            ];
            $xmlLoader = new InstallXmlLoader();
            $xmlLoader->setFixturesPath();

            foreach (array_chunk($xmlLoader->getSortedEntities(), 10) as $entity) {
                $fixturesStep['subtasks'][] = ['entity' => $entity];
            }

            $this->processSteps[] = $fixturesStep;
            $this->processSteps[] = [
                'key'  => 'initializeClasses',
                'lang' => $this->l('Initialize classes'),
            ];
        }

        $installPlugins = [
            'key'      => 'installPlugins',
            'lang'     => $this->l('Install plugins'),
            'subtasks' => [],
        ];

        foreach (array_chunk($this->modelInstall->getPluginsList(), 5) as $module) {
            $installPlugins['subtasks'][] = ['module' => $module];
        }

        $this->processSteps[] = $installPlugins;

        $this->processSteps[] = [
            'key'  => 'installTheme',
            'lang' => $this->l('Install theme'),
        ];

        $this->context->smarty->assign(
            [
                'install_is_done'  => $this->l('Done!'),
                'process_steps'    => $this->processSteps,
                'databaseLogin'    => $this->databaseLogin,
                'databasePassword' => $this->databasePassword,
                'databasePrefix'   => $this->databasePrefix,
                'errors'           => implode('<br />', $this->errors),
                'adminEmail'       => $this->session->adminEmail,
                'adminPassword'    => $this->session->adminPassword,
            ]
        );

        $this->setTemplate(_EPH_THEME_DIR_ . 'process.tpl');

    }

}
