<?php

/**
 * Step 2 : check system configuration (permissions on folders, PHP version, etc.)
 */
class SystemController extends InstallControllerHttp {

    public $tests = [];

    public $testsRender;

    /**
     * @var InstallModelSystem
     */
    public $modelSystem;

    public function __construct($step) {

        $this->step = 'system';
        $this->step_name = $this->l('System compatibility');
        parent::__construct($this->step);

    }

    /**
     * @see InstallAbstractModel::init()
     */
    public function init() {

        $this->modelSystem = new InstallModelSystem();
    }

    /**
     * @see InstallAbstractModel::processNextStep()
     */
    public function processNextStep() {}

    /**
     * Required tests must be passed to validate this step
     *
     * @see InstallAbstractModel::validate()
     */
    public function validate() {

        $this->tests['required'] = $this->modelSystem->checkRequiredTests();

        return $this->tests['required']['success'];
    }

    public function initContent() {

        parent::initContent();
        $this->validate();

        if (!isset($this->tests['required'])) {
            $this->tests['required'] = $this->modelSystem->checkRequiredTests();
        }

        if (!isset($this->tests['optional'])) {
            $this->tests['optional'] = $this->modelSystem->checkOptionalTests();
        }

        if (!is_callable('getenv') || !($user = @getenv('APACHE_RUN_USER'))) {
            $user = 'Apache';
        }

        // Generate display array
        $this->testsRender = [
            'required' => [
                [
                    'title'   => $this->l('Required PHP parameters'),
                    'success' => 1,
                    'checks'  => [
                        // This list should have a message for every test
                        // in ConfigurationTest::getDefaultTests().
                        // Exceptions: 'MysqlVersion', 'Files'
                        'Bcmath'                  => $this->l('The PHP bcmath extension is enabled'),
                        'CacheDir'                => $this->l('Cannot write to app/cache/'),
                        'LogDir'                  => $this->l('Cannot write to content/log/'),
                        'ImgDir'                  => $this->l('Cannot write to content/img/'),
                        'PluginDir'               => $this->l('Cannot write to includes/plugins/'),
                        'ThemeLangDir'            => $this->l('Cannot write to content/themes/phenyx-theme-default/lang/'),
                        'ThemeCacheDir'           => $this->l('Cannot write to content/themes/phenyx-theme-default/cache/'),
                        'TranslationsDir'         => $this->l('Cannot write to content/translations/'),
                        'CustomizableProductsDir' => $this->l('Cannot write to content/upload/'),
                        'ConfigDir'               => $this->l('Cannot write to app/'),
                        'MailsDir'                => $this->l('Cannot write to content/mails/'),
                        'System'                  => $this->l('Critical PHP functions exist'),
                        'Fopen'                   => $this->l('PHP‘s "allow_url_fopen" enabled'),
                        'Gd'                      => $this->l('GD library is installed'),
                        'Json'                    => $this->l('The PHP json extension is enabled'),
                        'MaxExecutionTime'        => $this->l('Max execution time is higher than 30'),
                        'Mbstring'                => $this->l('Mbstring extension is enabled'),
                        'OpenSSL'                 => $this->l('OpenSSL extension is enabled'),
                        'PdoMysql'                => $this->l('PDO MySQL extension is loaded'),
                        'PhpVersion'              => $this->l('PHP is 5.6.0 or later'),
                        'Upload'                  => $this->l('Cannot upload files'),
                        'Xml'                     => $this->l('The PHP xml extension is enabled'),
                        'Zip'                     => $this->l('The PHP zip extension/functionality is enabled'),
                    ],
                ],
                [
                    'title'   => $this->l('Files'),
                    'success' => 1,
                    'checks'  => [
                        'Files' => $this->l('Not all files were successfully uploaded on your server'),
                    ],
                ],
                [
                    'title'   => $this->l('Permissions on files and folders'),
                    'success' => 1,
                    'checks'  => [
                        'ConfigDir'               => sprintf($this->l('Recursive write permissions for %s user on %s'), $user, '~/config/'),
                        'CacheDir'                => sprintf($this->l('Recursive write permissions for %s user on %s'), $user, '~/cache/'),
                        'LogDir'                  => sprintf($this->l('Recursive write permissions for %s user on %s'), $user, '~/log/'),
                        'ImgDir'                  => sprintf($this->l('Recursive write permissions for %s user on %s'), $user, '~/img/'),
                        'MailsDir'                => sprintf($this->l('Recursive write permissions for %s user on %s'), $user, '~/mails/'),
                        'PluginDir'               => sprintf($this->l('Recursive write permissions for %s user on %s'), $user, '~/modules/'),
                        'ThemeLangDir'            => sprintf($this->l('Recursive write permissions for %s user on %s'), $user, '~/themes/' . _THEME_NAME_ . '/lang/'),
                        'ThemeCacheDir'           => sprintf($this->l('Recursive write permissions for %s user on %s'), $user, '~/themes/' . _THEME_NAME_ . '/cache/'),
                        'TranslationsDir'         => sprintf($this->l('Recursive write permissions for %s user on %s'), $user, '~/translations/'),
                        'CustomizableProductsDir' => sprintf($this->l('Recursive write permissions for %s user on %s'), $user, '~/upload/'),

                    ],
                ],
            ],
            'optional' => [
                [
                    'title'   => $this->l('Recommended PHP parameters'),
                    'success' => $this->tests['optional']['success'],
                    'checks'  => [
                        'Gz'     => $this->l('GZIP compression is not activated'),
                        'Tlsv12' => $this->l('Could not make a secure connection TLS 2. Your website might not be able to process some functionality.'),
                    ],
                ],
            ],
        ];

        foreach ($this->testsRender['required'] as &$category) {

            foreach ($category['checks'] as $id => $check) {
                $result = $this->tests['required']['checks'][$id];

                if ($result != 'ok') {
                    $category['success'] = 0;
                    $category['checks'][$id] .= ': ' . $result;
                }

            }

        }

        // If required tests failed, disable next button

        if (!$this->tests['required']['success']) {
            $this->nextButton = false;
        }

        $this->context->smarty->assign(
            [
                'testsRender'     => $this->testsRender,
                'tests'           => $this->tests,
                'install_version' => _EPH_INSTALL_VERSION_,
                'languages'       => $this->language->getLanguageList(),
                'detect_language' => $this->language->getLanguageIso(),
            ]
        );

        $this->setTemplate(_EPH_THEME_DIR_ . 'system.tpl');

    }

}
