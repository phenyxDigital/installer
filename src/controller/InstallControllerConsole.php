<?php

/**
 * Class InstallControllerConsole
 *
 * @since 1.0.0
 */
abstract class InstallControllerConsole {

    /**
     * @var array List of installer steps
     */
    protected static $steps = ['process'];

    protected static $instances = [];

    /**
     * @var string Current step
     */
    public $step;

    /**
     * @var array List of errors
     */
    public $errors = [];

    public $controller;

    /**
     * @var InstallSession
     */
    public $session;

    /**
     * @var InstallLanguages
     */
    public $language;

    /**
     * @var InstallAbstractModel
     */
    public $model;

    /** @var InstallModelInstall $modelInstall */
    public $modelInstall;

    /** @var Datas $datas */
    public $datas;

    /**
     * Validate current step
     */
    abstract public function validate();

    /**
     * @param $argc
     * @param $argv
     *
     * @throws PhenyxInstallerException
     *
     * @since 1.0.0
     */
    final public static function execute($argc, $argv) {

        if (!($argc - 1)) {
            $availableArguments = Datas::getInstance()->getArgs();
            echo 'Arguments available:' . "\n";

            foreach ($availableArguments as $key => $arg) {
                $name = isset($arg['name']) ? $arg['name'] : $key;
                echo '--' . $name . "\t" . (isset($arg['help']) ? $arg['help'] : '') . (isset($arg['default']) ? "\t" . '(Default: ' . $arg['default'] . ')' : '') . "\n";
            }

            exit;
        }

        $errors = Datas::getInstance()->getAndCheckArgs($argv);

        if (Datas::getInstance()->showLicense) {
            echo strip_tags(file_get_contents(_EPH_INSTALL_PATH_ . 'theme/views/license_content.phtml'));
            exit;
        }

        if ($errors !== true) {

            if (count($errors)) {

                foreach ($errors as $error) {
                    echo $error . "\n";
                }

            }

            exit;
        }

        if (!file_exists(_EPH_INSTALL_CONTROLLERS_PATH_ . 'console/process.php')) {
            throw new PhenyxInstallerException("Controller file 'console/process.php' not found");
        }

        require_once _EPH_INSTALL_CONTROLLERS_PATH_ . 'console/process.php';
        self::$instances['process'] = new InstallControllerConsoleProcess('process');

        $datas = Datas::getInstance();

        /* redefine HTTP_HOST  */
        $_SERVER['HTTP_HOST'] = $datas->httpHost;

        @date_default_timezone_set($datas->timezone);

        self::$instances['process']->process();
    }

    /**
     * InstallControllerConsole constructor.
     *
     * @param string $step
     *
     * @since 1.0.0
     * @throws PhenyxInstallerException
     */
    final public function __construct($step) {

        $this->step = $step;
        $this->datas = Datas::getInstance();

        // Set current language
        $this->language = InstallLanguages::getInstance();

        if (!$this->datas->language) {
            die('No language defined');
        }

        $this->language->setLanguage($this->datas->language);

        $this->init();
    }

    /**
     * Initialize model
     *
     * @since 1.0.0
     */
    public function init() {}

    /**
     * @since 1.0.0
     */
    public function printErrors() {

        $errors = $this->modelInstall->getErrors();

        if (count($errors)) {

            if (!is_array($errors)) {
                $errors = [$errors];
            }

            echo 'Errors :' . "\n";

            foreach ($errors as $errorProcess) {

                foreach ($errorProcess as $error) {
                    echo (is_string($error) ? $error : print_r($error, true)) . "\n";
                }

            }

            die;
        }

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
        return call_user_func_array([$this->language, 'l'], $args);
    }

    /**
     * @since 1.0.0
     */
    public function process() {}

}
