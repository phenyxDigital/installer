<?php

/**
 * Class PhenyxAutoload
 *
 * @since 1.9.1.0
 */
class PhenyxAutoload {

    // @codingStandardsIgnoreStart
    /**
     * File where classes index is stored
     */
    const INDEX_FILE = 'app/cache/class_index.php';
	
	


    /**
     * @var PhenyxAutoload
     */
    protected static $instance;
	
	public $_include_override_path = true;
	
    protected static $class_aliases = [
        'Collection' => 'PhenyxCollection',
        'Autoload'   => 'PhenyxAutoload',
        'Backup'     => 'PhenyxBackup',
        'Logger'     => 'PhenyxLogger',
    ];
    /**
     * @var array array('classname' => 'path/to/override', 'classnamecore' => 'path/to/class/core')
     */
    public $index = [];

   
    /**
     * @var string Root directory
     */
    protected $root_dir;
    // @codingStandardsIgnoreEnd

    /**
     * PhenyxAutoload constructor.
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function __construct() {

        
		$this->root_dir = _EPH_CORE_DIR_ . '/';
        $file = $this->normalizeDirectory(_EPH_ROOT_DIR_) . PhenyxAutoload::INDEX_FILE;

        if (@filemtime($file) && is_readable($file)) {
            $this->index = include $file;
        } else {
            $this->generateIndex();
        }

    }

    /**
     * @param $directory
     *
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function normalizeDirectory($directory) {

        return rtrim($directory, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Generate classes index
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function generateIndex() {

        $classes = $this->getClassesFromDir(_EPH_CLASS_DIR_);

        ksort($classes);
        $content = '<?php return ' . var_export($classes, true) . '; ?>';

        // Write classes index on disc to cache it
        $filename = $this->normalizeDirectory(_EPH_ROOT_DIR_) . PhenyxAutoload::INDEX_FILE;
        $filenameTmp = tempnam(dirname($filename), basename($filename . '.'));

        if ($filenameTmp !== false && file_put_contents($filenameTmp, $content) !== false) {

            if (!@rename($filenameTmp, $filename)) {
                unlink($filenameTmp);
            } else {
                @chmod($filename, 0666);

                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($filenameTmp);
                }

            }

        }

        // $filename_tmp couldn't be written. $filename should be there anyway (even if outdated), no need to die.
        else {
            Tools::error_log('Cannot write temporary file ' . $filenameTmp);
        }

        $this->index = $classes;
    }
	
	


    /**
     * Retrieve recursively all classes in a directory and its subdirectories
     *
     * @param string $path Relativ path from root to the directory
     *
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function getClassesFromDir($path, $hostMode = false) {

        $classes = [];
        $rootDir = $hostMode ? $this->normalizeDirectory(_EPH_ROOT_DIR_) : $this->root_dir;

        foreach (scandir($rootDir . $path) as $file) {

            if ($file[0] != '.') {

                if (is_dir($rootDir . $path . $file)) {
                    $classes = array_merge($classes, $this->getClassesFromDir($path . $file . '/', $hostMode));
                } else
                if (substr($file, -4) == '.php') {
                    $content = file_get_contents($rootDir . $path . $file);

                    $namespacePattern = '[\\a-z0-9_]*[\\]';
                    $pattern = '#\W((abstract\s+)?class|interface)\s+(?P<classname>' . basename($file, '.php') . '(?:Core)?)'
                        . '(?:\s+extends\s+' . $namespacePattern . '[a-z][a-z0-9_]*)?(?:\s+implements\s+' . $namespacePattern . '[a-z][\\a-z0-9_]*(?:\s*,\s*' . $namespacePattern . '[a-z][\\a-z0-9_]*)*)?\s*\{#i';

                    if (preg_match($pattern, $content, $m)) {
                        $classes[$m['classname']] = [
                            'path'     => $path . $file,
                            'type'     => trim($m[1]),
                            'override' => $hostMode,
                        ];

                        if (substr($m['classname'], -4) == 'Core') {
                            $classes[substr($m['classname'], 0, -4)] = [
                                'path'     => '',
                                'type'     => $classes[$m['classname']]['type'],
                                'override' => $hostMode,
                            ];
                        }

                    }

                }

            }

        }

        return $classes;
    }
    
   
    public static function getInstance() {

        if (!PhenyxAutoload::$instance) {
            PhenyxAutoload::$instance = new PhenyxAutoload();
        }

        return PhenyxAutoload::$instance;
    }

    public function load($className) {

        // Retrocompatibility

        if (isset(PhenyxAutoload::$class_aliases[$className]) && !interface_exists($className, false) && !class_exists($className, false)) {
            return eval('class ' . $className . ' extends ' . PhenyxAutoload::$class_aliases[$className] . ' {}');
        }

        // regenerate the class index if the requested file doesn't exists

        if ((isset($this->index[$className]) && $this->index[$className]['path'] && !is_file($this->root_dir . $this->index[$className]['path']))
            || (isset($this->index[$className . 'Core']) && $this->index[$className . 'Core']['path'] && !is_file($this->root_dir . $this->index[$className . 'Core']['path']))
        ) {
            $this->generateIndex();
        }

        // If $classname has not core suffix (E.g. Shop, Product)

        if (substr($className, -4) != 'Core') {
            $classDir = (isset($this->index[$className]['override'])
                && $this->index[$className]['override'] === true) ? $this->normalizeDirectory(_EPH_ROOT_DIR_) : $this->root_dir;

            // If requested class does not exist, load associated core class

            if (isset($this->index[$className]) && !$this->index[$className]['path']) {
                require_once $classDir . $this->index[$className . 'Core']['path'];

                if ($this->index[$className . 'Core']['type'] != 'interface') {
                    eval($this->index[$className . 'Core']['type'] . ' ' . $className . ' extends ' . $className . 'Core {}');
                }

            } else {
                // request a non Core Class load the associated Core class if exists

                if (isset($this->index[$className . 'Core'])) {
                    require_once $this->root_dir . $this->index[$className . 'Core']['path'];
                }

                if (isset($this->index[$className])) {
                    require_once $classDir . $this->index[$className]['path'];
                }

            }

        }

        // Call directly ProductCore, ShopCore class
        else
        if (isset($this->index[$className]['path']) && $this->index[$className]['path']) {
            require_once $this->root_dir . $this->index[$className]['path'];
        }

    }

    /**
     * @param string $className
     *
     * @return null
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getClassPath($className) {

        return (isset($this->index[$className]) && isset($this->index[$className]['path'])) ? $this->index[$className]['path'] : null;
    }

}
