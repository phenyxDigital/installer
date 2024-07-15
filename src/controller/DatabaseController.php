<?php

/**
 * Step 3 : configure database and email connection
 */
class DatabaseController extends InstallControllerHttp {

    /**
     * @var InstallModelDatabase
     */
    public $modelDatabase;

    public $databaseServer;
    public $databaseName;
    public $databaseLogin;
    public $databasePassword;
    public $databasePrefix;
    public $databaseClear;
    public $useSmtp;
    public $smtpEncryption;
    public $smtpPort;

    /**
     * @var InstallModelMail
     */
    public $modelMail;

    public function __construct($step) {

        $this->step = 'database';
        $this->step_name = $this->l('System configuration');
        parent::__construct($this->step);

    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);
        $this->addJS([
            _EPH_JS_DIR_ . 'database.js',

        ]);

    }

    public function init() {

        $this->modelDatabase = new InstallModelDatabase();
    }

    /**
     * @see InstallAbstractModel::processNextStep()
     */
    public function processNextStep() {

        // Save database config
        $this->session->databaseServer = trim(Tools::getValue('dbServer'));
        $this->session->databaseName = trim(Tools::getValue('dbName'));
        $this->session->databaseLogin = trim(Tools::getValue('dbLogin'));
        $this->session->databasePassword = trim(Tools::getValue('dbPassword'));
        $this->session->databasePrefix = trim(Tools::getValue('db_prefix'));
        $this->session->databaseClear = Tools::getValue('database_clear');
        $this->session->rewriteEngine = Tools::getValue('rewrite_engine');
    }

    /**
     * Database configuration must be valid to validate this step
     *
     * @see InstallAbstractModel::validate()
     */
    public function validate() {

        $this->errors = $this->modelDatabase->testDatabaseSettings(
            $this->session->databaseServer,
            $this->session->databaseName,
            $this->session->databaseLogin,
            $this->session->databasePassword,
            $this->session->databasePrefix,
            // We do not want to validate table prefix if we are already in install process
            ($this->session->step == 'process') ? true : $this->session->databaseClear
        );

        if (count($this->errors)) {
            return false;
        }

        return true;
    }

    public function process() {

        if (Tools::getValue('checkDb')) {
            $this->processCheckDb();
        } else
        if (Tools::getValue('createDb')) {
            $this->processCreateDb();
        }

    }

    /**
     * Check if a connection to database is possible with these data
     */
    public function processCheckDb() {

        $server = Tools::getValue('dbServer');
        $database = Tools::getValue('dbName');
        $login = Tools::getValue('dbLogin');
        $password = Tools::getValue('dbPassword');
        $prefix = Tools::getValue('db_prefix');
        $clear = Tools::getValue('clear');

        $errors = $this->modelDatabase->testDatabaseSettings($server, $database, $login, $password, $prefix, $clear);

        $this->ajaxJsonAnswer(
            (count($errors)) ? false : true,
            (count($errors)) ? implode('<br />', $errors) : $this->l('Database is connected')
        );
    }

    /**
     * Attempt to create the database
     */
    public function processCreateDb() {

        $server = Tools::getValue('dbServer');
        $database = Tools::getValue('dbName');
        $login = Tools::getValue('dbLogin');
        $password = Tools::getValue('dbPassword');

        $success = $this->modelDatabase->createDatabase($server, $database, $login, $password);

        $this->ajaxJsonAnswer(
            $success,
            $success ? $this->l('Database is created') : $this->l('Cannot create the database automatically')
        );
    }

    public function initContent() {

        parent::initContent();
        $this->processNextStep();

        if (!$this->session->databaseServer) {

            if (file_exists(_EPH_WEBSITE_DIR_ . '/app/settings.inc.php')) {
                @include_once _EPH_WEBSITE_DIR_ . '/app/settings.inc.php';
                $this->databaseServer = _DB_SERVER_;
                $this->databaseName = _DB_NAME_;
                $this->databaseLogin = _DB_USER_;
                $this->databasePassword = _DB_PASSWD_;
                $this->databasePrefix = _DB_PREFIX_;
            } else {
                $this->databaseServer = 'localhost';
                $this->databaseName = 'phenyx_digital';
                $this->databaseLogin = 'root';
                $this->databasePassword = '';
                $this->databasePrefix = 'eph_';
            }

            $this->databaseClear = true;
            $this->useSmtp = false;
            $this->smtpEncryption = 'off';
            $this->smtpPort = 25;
        } else {
            $this->databaseServer = $this->session->databaseServer;
            $this->databaseName = $this->session->databaseName;
            $this->databaseLogin = $this->session->databaseLogin;
            $this->databasePassword = $this->session->databasePassword;
            $this->databasePrefix = $this->session->databasePrefix;
            $this->databaseClear = $this->session->databaseClear;

            $this->useSmtp = $this->session->useSmtp;
            $this->smtpEncryption = $this->session->smtpEncryption;
            $this->smtpPort = $this->session->smtpPort;
        }

        $this->context->smarty->assign(
            [
                'databaseServer'   => $this->databaseServer,
                'databaseName'     => $this->databaseName,
                'databaseLogin'    => $this->databaseLogin,
                'databasePassword' => $this->databasePassword,
                'databasePrefix'   => $this->databasePrefix,
                'databaseClear'   => $this->databaseClear,
                'errors'           => implode('<br />', $this->errors),
            ]
        );

        $this->setTemplate(_EPH_THEME_DIR_ . 'database.tpl');

    }

}
