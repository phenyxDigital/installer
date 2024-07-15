<?php

abstract class InstallController {

    /**
     * @var string[] List of installer steps
     */
    protected static $steps = ['welcome', 'license', 'system', 'configure', 'database', 'process'];

    /**
     * @var InstallControllerHttp[]
     */
    protected static $instances = [];
    
    public $php_self;
    
    public $css_files = [];
    
    public $js_files = [];
    
    public $js_def = [];
    
    public $ajax;

    /**
     * @var string Current step
     */
    public $step;
    
    public $step_name;

    /**
     * @var string Last step
     */
    public $lastStep;

    /**
     * @var array List of errors
     */
    public $errors = [];
    public $controller;
    
    public  $context;
    /**
     * @var InstallSession
     */
    public $session;
    /**
     * @var InstallLanguages
     */
    public $language;
    /**
     * @var bool If false, disable next button access
     */
    public $nextButton = true;
    /**
     * @var bool If false, disable previous button access
     */
    public $previousButton = true;
    /**
     * @var InstallAbstractModel
     */
    public $model;
    
    public $layout;
    
    public $template;
    
    protected $phone;
    /**
     * @var array Magic vars
     */
    protected $vars = [];

    /**
     * InstallControllerHttp constructor.
     *
     * @param string $step
     *
     * @since 1.0.0
     * @throws PhenyxInstallerException
     * @throws PhenyxException
     */
    public function __construct($step) {
          
       $this->context = Context::getContext();
        $this->context->step = $step;
        $this->ajax = Tools::getValue('ajax') || Tools::isSubmit('ajax');

        $this->step = $step;
        $this->session = InstallSession::getInstance();
        $file = fopen("testInstallConstruct.txt","w");

        // Set current language
        $this->language = InstallLanguages::getInstance();
        if(Tools::getValue('lang')) {
            $lang = Tools::getValue('lang');
            $this->session->lang = $lang;
        } else  if (isset($this->session->lang)) {
            $lang = $this->session->lang;
        } else {
            $detectLanguage = $this->language->detectLanguage();
            $lang = (isset($detectLanguage['primarytag'])) ? $detectLanguage['primarytag'] : false;
        }

        if (!in_array($lang, $this->language->getIsoList())) {
            $lang = 'en';
        } else if(Tools::getValue('lang')) {
            $lang = Tools::getValue('lang');
        }
        fwrite($file,$lang.PHP_EOL);
         if(file_exists(_EPH_THEME_DIR_.'lang/'.$lang.'.php')) {
            include_once _EPH_THEME_DIR_.'lang/'.$lang.'.php';
        }
        $this->language->setLanguage($lang);
       
        $this->context->language = new InstallLanguage($lang);
         fwrite($file,print_r($this->context->language, true));
        $this->context->link = new Link();

        $this->init();
    }
    
    public function run() {

        $this->init();
        
         $this->setMedia();
        
        $this->postProcess();
        
        $this->process();
        
        $this->initContent();
        
        $this->display();
    }
    
    public function getStepName() {
        
        switch ($this->step) {
            case 'welcome':
                return $this->l('Choose your language');
                break;
            case 'license':
                return $this->l('License agreements');
                break;
            case 'system':
                return $this->l('System compatibility');
                break;
            case 'configure':
                return $this->l('CMS information');
                break;
            case 'database':
                return $this->l('System configuration');
                break;
            case 'process':
                return $this->l('CMS installation');
                break;
        }
        
    }
    
    public function getNameByStep($step) {
        
        switch ($step) {
            case 'welcome':
                return $this->l('Choose your language');
                break;
            case 'license':
                return $this->l('License agreements');
                break;
            case 'system':
                return $this->l('System compatibility');
                break;
            case 'configure':
                return $this->l('CMS information');
                break;
            case 'database':
                return $this->l('System configuration');
                break;
            case 'process':
                return $this->l('CMS installation');
                break;
        }
        
    }


    /**
     * @since 1.0.0
     */
    abstract public function init();
    
    abstract public function process();
    
    public function setMedia($isNewTheme = false) {     

        $this->addJS([
            _EPH_JS_DIR_.'jquery/jquery-'._EPH_JQUERY_VERSION_.'.min.js',
            _EPH_JS_DIR_.'jquery-ui/jquery-ui.min.js',
            _EPH_JS_DIR_.'jquery/plugins/jquery.chosen.js',

        ]);
        
         $this->addCSS([
             _EPH_CSS_DIR_ . 'view.css',
             _EPH_CSS_DIR_ . 'autoload/jquery-ui.css',
             _EPH_CSS_DIR_ . 'autoload/all.css',
         ]);
    }
    
    public function postProcess() {

        try {

            if ($this->ajax) {
                // from ajax-tab.php
                $action = Tools::getValue('action');
                // no need to use displayConf() here

                if (!empty($action) && method_exists($this, 'ajaxProcess' . Tools::toCamelCase($action))) {
                    $return = $this->{'ajaxProcess' . Tools::toCamelCase($action)}

                    ();
                    return $return;
                }

            }

        } catch (PhenyxException $e) {
            $this->errors[] = $e->getMessage();
        };

        return false;
    }
    
    public function initContent() {}
    
    
    public function addJsDef($jsDef) {
        $this->js_def = [];
        if (is_array($jsDef)) {

            foreach ($jsDef as $key => $js) {
                // @codingStandardsIgnoreStart
                $this->js_def[$key] = $js;
                // @codingStandardsIgnoreEnd
            }

        } else if ($jsDef) {
            // @codingStandardsIgnoreStart
            $this->js_def[] = $jsDef;
            // @codingStandardsIgnoreEnd
        }

    }
    
    public function addJS($jsUri, $checkPath = true) {
        if (is_array($jsUri)) {
            foreach ($jsUri as $jsFile) {
                $jsFile = explode('?', $jsFile);
                $version = '';

                if (isset($jsFile[1]) && $jsFile[1]) {
                    $version = $jsFile[1];
                }

                $jsPath = $jsFile = $jsFile[0];

                if ($checkPath) {
                    $jsPath = Tools::getMediaPath($jsFile);
                }

                // $key = is_array($js_path) ? key($js_path) : $js_path;

                if ($jsPath && !in_array($jsPath, $this->js_files)) {
                    $this->js_files[] = $jsPath . ($version ? '?' . $version : '');
                }

            }

        } else {
            
            $jsUri = explode('?', $jsUri);
            $version = '';

            if (isset($jsUri[1]) && $jsUri[1]) {
                $version = $jsUri[1];
            }

            $jsPath = $jsUri = $jsUri[0];

            if ($checkPath) {
                $jsPath = Tools::getMediaPath($jsUri);
            }

            if ($jsPath && !in_array($jsPath, $this->js_files)) {
                $this->js_files[] = $jsPath . ($version ? '?' . $version : '');
            }

        }

    }
    
     public function addCSS($cssUri, $cssMediaType = 'all', $offset = null, $checkPath = true) {
       
        if (!is_array($cssUri)) {
            $cssUri = [$cssUri];
        }
         
        foreach ($cssUri as $cssFile => $media) {

            if (is_string($cssFile) && strlen($cssFile) > 1) {

                if ($checkPath) {
                    $cssPath = Tools::getCSSPath($cssFile, $media);
                } else {
                    $cssPath = [$cssFile => $media];
                }

            } else {

                if ($checkPath) {
                    $cssPath = Tools::getCSSPath($media, $cssMediaType);
                } else {
                    $cssPath = [$media => is_string($cssMediaType) ? $cssMediaType : 'all'];
                }

            }

            $key = is_array($cssPath) ? key($cssPath) : $cssPath;

            if ($cssPath && (!isset($this->css_files[$key]) || ($this->css_files[$key] != reset($cssPath)))) {
                $size = count($this->css_files);

                if ($offset === null || $offset > $size || $offset < 0 || !is_numeric($offset)) {
                    $offset = $size;
                }

                $this->css_files = array_merge(array_slice($this->css_files, 0, $offset), $cssPath, array_slice($this->css_files, $offset));
            }

        }
        

    }



    
    protected function smartyOutputContent($content) {

        $file = fopen("testsmartyOutputContent.txt","w");
        $html = '';
        fwrite($file,$content.PHP_EOL);
        $html = $this->context->smarty->fetch($content);
         

        $html = trim($html);
        fwrite($file,$html.PHP_EOL);

        echo $html;

    }
    
    /**
     * Process form to go to next step
     */
    abstract public function processNextStep();

    /**
     * Validate current step
     */
    abstract public function validate();

    /**
     * Display current step view
     */
    abstract public function display();
    

    protected function l($string, $class = null, $addslashes = false, $htmlentities = true) {

        if ($class === null) {
            $class = get_class($this);
        } 

        return Translate::getAdminTranslation($string, $class, $addslashes, $htmlentities);
    }
    
    public function setTemplate($template) {

        $this->template = $template;
    }
    
    public function ajaxProcessSetLanguage() {
        
        $lang = Tools::getValue('lang');
        $this->language->setLanguage($lang);
       
        $this->context->language = new InstallLanguage($lang);


        die(true);
    }


}
