<?php

/**
 * Step 2 : display license form
 */
class LicenseController extends InstallControllerHttp {

    /** @var InstallSession $session */
    public $session;

    public function __construct($step) {

        $this->step = 'license';
        $this->step_name = $this->l('License agreements');
        parent::__construct($this->step);

    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);
        $this->addJS([
            _EPH_JS_DIR_ . 'license.js',

        ]);

    }

    public function initContent() {

        parent::initContent();
        $this->processNextStep();

        $this->context->smarty->assign(
            [
                'licenseAgreement' => $this->session->licenseAgreement,
            ]
        );

        $this->setTemplate(_EPH_THEME_DIR_ . 'license.tpl');

    }

    /**
     * Process license form
     *
     * @see InstallAbstractModel::process()
     */
    public function processNextStep() {

        $this->session->licenseAgreement = Tools::getValue('license_agreement');
        $this->session->configurationAgreement = Tools::getValue('configuration_agreement');
    }

    /**
     * Licence agrement must be checked to validate this step
     *
     * @see InstallAbstractModel::validate()
     */
    public function validate() {

        return $this->session->licenseAgreement;
    }

    public function process() {}

}
