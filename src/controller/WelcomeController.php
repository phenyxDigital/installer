<?php

/**
 * Step 1 : display language form
 */
class WelcomeController extends InstallControllerHttp {

    public $php_self = 'welcome';
    public $canUpgrade;
    public $ephVersion;

    public function processNextStep() {}

    public function validate() {

        return true;
    }

    public function __construct($step) {

        $this->step = 'welcome';
        $this->step_name = $this->l('Choose your language');
        parent::__construct($this->step);

    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);
        $this->addJS([
            _EPH_JS_DIR_ . 'welcome.js',

        ]);

    }

    public function initContent() {

        parent::initContent();

        $this->canUpgrade = false;

        if (file_exists(_EPH_WEBSITE_DIR_ . '/app/settings.inc.php')) {
            @include_once _EPH_WEBSITE_DIR_ . '/app/settings.inc.php';

            if (version_compare(_EPH_VERSION_, _EPH_INSTALL_VERSION_, '<')) {
                $this->canUpgrade = true;
                $this->ephVersion = _EPH_VERSION_;
            }

        }

        $this->context->smarty->assign(
            [
                'canUpgrade'      => $this->canUpgrade,
                'ephVersion'      => _EPH_VERSION_,
                'install_version' => _EPH_INSTALL_VERSION_,
                'languages'       => $this->language->getLanguageList(),
                'detect_language' => $this->language->getLanguageIso(),
            ]
        );

        $this->setTemplate(_EPH_THEME_DIR_ . 'welcome.tpl');

    }

}
