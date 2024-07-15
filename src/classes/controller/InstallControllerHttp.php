<?php

class InstallControllerHttp extends InstallController {

    public function __construct($step) {

        parent::__construct($step);

    }

    public function init() {

        // If current URL use SSL, set it true (used a lot for plugin redirect)

        if (Tools::usingSecureMode()) {
            $useSSL = true;
        }

        // For compatibility with globals, DEPRECATED as of version 1.5.0.1
        $cssFiles = $this->css_files;
        $jsFiles = $this->js_files;

        ob_start();

        /* Theme is missing */

        if (!is_dir(_EPH_THEME_DIR_)) {
            throw new PhenyxException((sprintf($this->l('Current theme unavailable "%s". Please check your theme directory name and permissions.'), basename(rtrim(_EPH_THEME_DIR_, '/\\')))));
        }

        $pageName = '';

        if (!empty($this->php_self)) {
            $pageName = $this->php_self;
        }

        $this->context->smarty->assign('request_uri', Tools::safeOutput(urldecode($_SERVER['REQUEST_URI'])));

        $this->context->smarty->assign(
            [

                'link'      => $this->context->link,
                'cookie'    => $this->context->cookie,
                'page_name' => $pageName,
                'tpl_dir'   => _EPH_THEME_DIR_,
                'tpl_uri'   => _THEME_DIR_,
                'lang_iso'  => $this->context->language->iso,
            ]
        );

        $assignArray = [
            'img_dir' => _THEME_IMG_DIR_,
            'css_dir' => _EPH_CSS_DIR_,
            'js_dir'  => _EPH_JS_DIR_,
        ];

    }

    public function initContent() {

        $steps = [];
        $stages = $this->getSteps();

        foreach ($stages as $stage) {
            $steps[$stage] = [
                'finished' => $this->isStepFinished($stage),
                'lastStep' => $this->getLastStep(),
                'name'     => $this->getNameByStep($stage),
            ];
        }

        $this->context->smarty->assign(
            [
                'favicon_url'          => _THEME_IMG_DIR_ . 'favicon.ico',
                'logo_url'             => _THEME_IMG_DIR_ . 'logo.png',
                'steps'                => $steps,
                'curent_step'          => $this->step,
                'isLastStep'           => $this->isLastStep(),
                'nextButton'           => $this->nextButton,
                'isFirstStep'          => $this->isFirstStep(),
                'previousButton'       => $this->previousButton,
                'getTailoredHelp'      => $this->getTailoredHelp(),
                'getDocumentationLink' => $this->getDocumentationLink(),
            ]
        );

    }

    /**
     * @throws PhenyxInstallerException
     * @throws PhenyxException
     *
     * @since 1.0.0
     */
    public static function execute() {

        // catch and report all fatal errors
        register_shutdown_function([__CLASS__, 'shutdownHandler']);

        $session = InstallSession::getInstance();

        // Include all controllers

        foreach (self::$steps as $step) {

            $classname = ucfirst($step) . 'Controller';

            if (!class_exists($classname)) {
                throw new PhenyxInstallerException("Controller file '{$classname}.php' not found");
            }

            self::$instances[$step] = new $classname($step);
        }

        if (!$session->lastStep || !in_array($session->lastStep, self::$steps)) {
            $session->lastStep = self::$steps[0];
        }

        // Set timezone

        if ($session->shopTimezone) {
            @date_default_timezone_set($session->shopTimezone);
        }

        // Get current step (check first if step is changed, then take it from session)

        if (Tools::getValue('step')) {
            $currentStep = Tools::getValue('step');
            $session->step = $currentStep;
        } else {
            $currentStep = (isset($session->step)) ? $session->step : self::$steps[0];
        }

        if (!in_array($currentStep, self::$steps)) {
            $currentStep = self::$steps[0];
        }

        // Validate all steps until current step. If a step is not valid, use it as current step.

        foreach (self::$steps as $checkStep) {
            // Do not validate current step

            if ($checkStep == $currentStep) {
                break;
            }

            if (!self::$instances[$checkStep]->validate()) {
                $currentStep = $checkStep;
                $session->step = $currentStep;
                $session->lastStep = $currentStep;
                break;
            }

        }

        try {
            // Submit form to go to next step

            if (Tools::getValue('submitNext')) {
                self::$instances[$currentStep]->processNextStep();

                // If current step is validated, let's go to next step

                if (self::$instances[$currentStep]->validate()) {
                    $currentStep = self::$instances[$currentStep]->findNextStep();
                }

                $session->step = $currentStep;

                // Change last step

                if (self::getStepOffset($currentStep) > self::getStepOffset($session->lastStep)) {
                    $session->lastStep = $currentStep;
                }

            } else
            if (Tools::getValue('submitPrevious') && $currentStep != self::$steps[0]) {
                // Go to previous step
                $currentStep = self::$instances[$currentStep]->findPreviousStep();
                $session->step = $currentStep;
            }

            self::$instances[$currentStep]->run();
            //self::$instances[$currentStep]->display();
        } catch (Exception $e) {
            static::sendErrorResponse($e->getMessage(), $e);
        }

    }

    public function processNextStep() {}

    public function validate() {}

    public function process() {}

    public function display() {

        Tools::safePostVars();

        $this->context->smarty->assign(
            [
                'css_files'   => $this->css_files,
                'js_files'    => $this->js_files,
                'img_formats' => ['webp' => 'image/webp', 'jpg' => 'image/jpeg'],

            ]
        );

        $layout = $this->getLayout();

        if ($layout) {

            if ($this->template) {
                $template = $this->context->smarty->fetch($this->template);
                $this->context->smarty->assign('template', $template);
                $this->smartyOutputContent($layout);
            }

        } else {
            Tools::displayAsDeprecated('layout.tpl is missing in your theme directory');

        }

        return true;
    }

    public function getLayout() {

        $layoutDir = $this->getThemeDir();

        if (!$this->layout && file_exists($layoutDir . 'layout.tpl')) {
            $this->layout = $layoutDir . 'layout.tpl';
        }

        return $this->layout;
    }

    protected function getThemeDir() {

        return _EPH_THEME_DIR_;
    }

    /**
     * Find offset of a step by name
     *
     * @param string $step Step name
     *
     * @return int
     *
     * @since 1.0.0
     */
    public static function getStepOffset($step) {

        static $flip = null;

        if (is_null($flip)) {
            $flip = array_flip(self::$steps);
        }

        return $flip[$step];
    }

    /**
     * Get steps list
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getSteps() {

        return self::$steps;
    }

    /**
     * Make a HTTP redirection to a step
     *
     * @param string $step
     */
    public function redirect($step) {

        header('location: index.php?step=' . $step);
        exit;
    }

    /**
     * Find previous step
     *
     * @return bool|string
     */
    public function findPreviousStep() {

        return (isset(self::$steps[$this->getStepOffset($this->step) - 1])) ? self::$steps[$this->getStepOffset($this->step) - 1] : false;
    }

    /**
     * Find next step
     *
     * @return bool|mixed
     */
    public function findNextStep() {

        $nextStep = (isset(self::$steps[$this->getStepOffset($this->step) + 1])) ? self::$steps[$this->getStepOffset($this->step) + 1] : false;

        if ($nextStep == 'system' && self::$instances[$nextStep]->validate()) {
            $nextStep = self::$instances[$nextStep]->findNextStep();
        }

        return $nextStep;
    }

    /**
     * Check if current step is first step in list of steps
     *
     * @return bool
     */
    public function isFirstStep() {

        return self::getStepOffset($this->step) == 0;
    }

    /**
     * Check if current step is last step in list of steps
     *
     * @return bool
     */
    public function isLastStep() {

        return self::getStepOffset($this->step) == (count(self::$steps) - 1);
    }

    /**
     * Check is given step is already finished
     *
     * @param string $step
     *
     * @return bool
     */
    public function isStepFinished($step) {

        return self::getStepOffset($step) < self::getStepOffset($this->getLastStep());
    }

    /**
     * @return mixed|null
     *
     * @since 1.0.0
     */
    public function getLastStep() {

        return $this->session->lastStep;
    }

    /**
     * Get telephone used for this language
     *
     * @return string
     */
    public function getPhone() {

        return '';
    }

    /**
     * Get link to documentation for this language
     *
     * Enter description here ...
     */
    public function getDocumentationLink() {

        return $this->language->getInformation('documentation');
    }

    /**
     * Get link to tailored help for this language
     *
     * Enter description here ...
     */
    public function getTailoredHelp() {

        return $this->language->getInformation('tailored_help');
    }

    /**
     * Get link to forum for this language
     *
     * Enter description here ...
     */
    public function getForumLink() {

        return $this->language->getInformation('forum');
    }

    /**
     * Get link to blog for this language
     *
     * Enter description here ...
     */
    public function getBlogLink() {

        return $this->language->getInformation('blog');
    }

    /**
     * Get link to support for this language
     *
     * Enter description here ...
     */
    public function getSupportLink() {

        return $this->language->getInformation('support');
    }

    /**
     * Send AJAX response in JSON format {success: bool, message: string[]}
     *
     * @param bool  $success
     * @param array $message Messages array
     *
     * @since 1.0.0
     */
    public function ajaxJsonAnswer($success, $message = []) {

        static::sendJsonResponse($success, $message);
    }

    /**
     * Sends AJAX response in JSON format {success: bool, message: string[]}
     *
     * @param boolean $success
     * @param array $message
     */
    public static function sendJsonResponse($success, $message = []) {

        if (!$success && empty($message)) {
            $message = print_r(@error_get_last(), true);
        }

        die(json_encode(
            [
                'success' => (bool) $success,
                'message' => $message,
            ]
        ));
    }

    /**
     * This method displays error response page
     *
     * Different output is emitted depending on request context - json for ajax
     * requests, http error page for regular requests
     *
     * @param string $error
     * @param Exception $e original exception
     * @throws PhenyxInstallerException
     */
    public static function sendErrorResponse($error, $e = null) {

        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

        if ($isAjax) {
            // send ajax response for ajax requests
            static::sendJsonResponse(false, [$error]);
        } else {
            // throw PhenyxInstallerException for regular requests. This exception will be
            // converted to error page by root exception handler in trystart.php

            if (!($e instanceof PhenyxInstallerException)) {
                $e = new PhenyxInstallerException($error, 0, $e);
            }

            throw $e;
        }

    }

    /**
     * Display a template
     *
     * @param string $template  Template name
     * @param bool   $getOutput Is true, return template html
     *
     * @param null   $path
     *
     * @return string
     * @throws PhenyxInstallerException
     * @since 1.0.0
     */
    public function displayTemplate($template, $getOutput = false, $path = null) {

        if (!$path) {
            $path = _EPH_INSTALL_PATH_ . 'theme/views/';
        }

        if (!file_exists($path . $template . '.phtml')) {
            throw new PhenyxInstallerException("Template '{$template}.phtml' not found");
        }

        if ($getOutput) {
            ob_start();
        }

        include $path . $template . '.phtml';

        if ($getOutput) {
            $content = ob_get_contents();

            if (ob_get_level() && ob_get_length() > 0) {
                ob_end_clean();
            }

            return $content;
        }

        return '';
    }

    /**
     * This method is called after script execution finishes or exit is called. If the script was
     * terminated because of fatal error, we will collect and send this information to the client
     *
     * @throws PhenyxInstallerException
     */
    public static function shutdownHandler() {

        $error = error_get_last();

        if ($error && static::isFatalError($error['type'])) {
            static::sendErrorResponse($error['message']);
        }

    }

    /**
     * Returns true, if $errno is a fatar error
     *
     * @param int $errno
     * @return bool
     */
    public static function isFatalError($errno) {

        return (
            $errno === E_USER_ERROR ||
            $errno === E_ERROR ||
            $errno === E_CORE_ERROR ||
            $errno === E_COMPILE_ERROR ||
            $errno === E_RECOVERABLE_ERROR
        );
    }

}
