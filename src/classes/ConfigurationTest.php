<?php

/**
 * Class ConfigurationTest
 *
 * @since 1.0.0
 */
class ConfigurationTest {

    /**
     * @var array $testFiles
     *
     * @since 1.0.0 Renamed from $test_files
     */
    public static $testFiles = [
        '/app/cache/smarty/compile',
        '/app',
        '/includes/controllers/backend/AdminLoginController.php',
        '/vendor/autoload.php',
        '/content/css',
        '/content/upload',
        '/content/img/404.gif',
        '/content/js/tools.js',
        '/content/js/jquery/plugins/fancybox/jquery.fancybox.js',
        '/content/localization/fr.xml',
        '/content/mails',
        '/includes/plugins',
        '/content/themes/phenyx-theme-default/css/global.css',
        '/content/translations/export',
        '/webephenyx/dispatcher.php',
    ];

    /**
     * getDefaultTests return an array of tests to executes.
     * key are method name, value are parameters (false for no parameter)
     * all path are _EPH_WEBSITE_DIR_ related
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getDefaultTests() {

        $tests = [
            // Changing this list also requires ajusting the list of matching
            // error messages in install-dev/controllers/http/system.php
            'Upload'                  => false,
            'CacheDir'                => 'app/cache',
            'LogDir'                  => 'content/log',
            'ImgDir'                  => 'content/img',
            'PluginDir'               => 'includes/plugins',
            'ThemeLangDir'            => 'content/themes/' . _THEME_NAME_ . '/lang/',
            'ThemeCacheDir'           => 'content/themes/' . _THEME_NAME_ . '/cache/',
            'TranslationsDir'         => 'content/translations',
            'CustomizableProductsDir' => 'content/upload',
            'System'                  => [
                'fopen', 'fclose', 'fread', 'fwrite',
                'rename', 'file_exists', 'unlink', 'rmdir', 'mkdir',
                'getcwd', 'chdir', 'chmod',
            ],
            'PhpVersion'              => false,
            'Fopen'                   => false,
            'ConfigDir'               => 'app',
            'Files'                   => false,
            'MailsDir'                => 'content/mails',
            'MaxExecutionTime'        => false,
            'MysqlVersion'            => false,
            // PHP extensions.
            'Bcmath'                  => false,
            'Gd'                      => false,
            'Json'                    => false,
            'Mbstring'                => false,
            'OpenSSL'                 => false,
            'PdoMysql'                => false,
            'Xml'                     => false,
            'Zip'                     => false,
        ];

        return $tests;
    }

    /**
     * getDefaultTestsOp return an array of tests to executes.
     * key are method name, value are parameters (false for no parameter)
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getDefaultTestsOp() {

        return [
            'Gz'     => false,
            'Tlsv12' => false,
        ];
    }

    /**
     * run all test defined in $tests
     *
     * @param array $tests
     *
     * @return array results of tests
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function check($tests) {

        $res = [];

        foreach ($tests as $key => $test) {
            $res[$key] = static::run($key, $test);
        }

        return $res;
    }

    /**
     * @param string $ptr
     * @param int    $arg
     *
     * @return string 'ok' on success, 'fail' or error message on failure.
     *
     * @since   1.0.2 Also report error message.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function run($ptr, $arg = 0)
    {
        if (call_user_func(array('ConfigurationTest', 'test'.$ptr), $arg)) {
            return 'ok';
        }
        return 'fail';
    }
    
    /**
     * @return bool
     *
     * @since   1.0.0
     * @since   1.0.8 Fill error report.
     * @version 1.0.0 Initial version
     */
    public static function testPhpVersion(&$report = null) {

        if (version_compare(PHP_VERSION, '8.2', '<')) {
            $report = sprintf('PHP version is %s, should be at least version 8.2.', PHP_VERSION);

            return false;
        }

        return true;
    }

    /**
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testMysqlSupport() {

        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testPdoMysql() {

        return extension_loaded('pdo_mysql');
    }

    /**
     * @return bool
     *
     * @since   1.0.8
     */
    public static function testMysqlVersion(&$report = null) {

        if (defined('_DB_SERVER_') && defined('_DB_USER_')
            && defined('_DB_PASSWD_') && defined('_DB_NAME_')) {
            $version = Db::getInstance()->getVersion();

            if (version_compare($version, '5.5', '<')) {
                $report = sprintf('DB server is v%s, should be at least MySQL v5.5.3 or MariaDB v5.5.', $version);

                return false;
            }

        }

        // Else probably installation time.

        return true;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public static function testBcmath() {

        return extension_loaded('bcmath') && function_exists('bcdiv');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public static function testXml() {

        return class_exists('SimpleXMLElement');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public static function testJson() {

        return function_exists('json_encode') && function_exists('json_decode');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public static function testZip() {

        return class_exists('ZipArchive');
    }

    /**
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testMagicQuotes() {

        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testUpload() {

        return ini_get('file_uploads');
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testFopen() {

        return ini_get('allow_url_fopen');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public static function testTlsv12() {

        $ch = curl_init('https://www.howsmyssl.com/a/check');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($data);

        if ($json->tls_version == 'TLS 1.2') {
            return true;
        }

        return false;
    }

    /**
     * @param array $funcs
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testSystem($funcs, &$report = null) {


        foreach ($funcs as $func) {

            if (!function_exists($func)) {
                $report = 'Function ' . $func . '() does not exist.';
                return false;
            }

        }

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testGd() {

        return function_exists('imagecreatetruecolor');
    }

    /**
     * @return bool
     *
     * @since   1.0.1
     * @version 1.0.1 Initial version
     */
    public static function testMaxExecutionTime() {

        return ini_get('max_execution_time') <= 0
        || ini_get('max_execution_time') >= 30;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testGz() {

        if (function_exists('gzencode')) {
            return @gzencode('dd') !== false;
        }

        return false;
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testConfigDir($dir, &$report = null) {

        return static::testDir($dir, false, $report);
    }

    /**
     * Test if directory is writable
     *
     * @param string $dir        Directory path, absolute or relative
     * @param bool   $recursive
     * @param null   $fullReport
     * @param bool   $absolute   Is absolute path to directory
     *
     * @return bool
     *
     * @since   1.0.0 Added $absolute parameter
     * @version 1.0.0 Initial version
     */
    public static function testDir($dir, $recursive = false, &$fullReport = null, $absolute = false) {
        
       
        if ($absolute) {
            $absoluteDir = $dir;
        } else {
            $absoluteDir = rtrim(_EPH_WEBSITE_DIR_, '\\/') . DIRECTORY_SEPARATOR . trim($dir, '\\/');
        }
        

        if (!file_exists($absoluteDir)) {
            $fullReport = sprintf('Directory %s does not exist.', $absoluteDir);

            return false;
        }

        if (!is_writable($absoluteDir)) {
            $fullReport = sprintf('Directory %s is not writable.', $absoluteDir);

            return false;
        }

        if ($recursive) {

            foreach (scandir($absoluteDir, SCANDIR_SORT_NONE) as $item) {
                $path = $absoluteDir . DIRECTORY_SEPARATOR . $item;

                if (in_array($item, ['.', '..', '.git'])
                    || is_link($path)) {
                    continue;
                }

                if (is_dir($path)) {

                    if (!static::testDir($path, $recursive, $fullReport, true)) {
                        return false;
                    }

                }

                if (!is_writable($path)) {
                    $fullReport = sprintf('File %s is not writable.', $path);
                    return false;
                }

            }

        }

        return true;
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testSitemap($dir, &$report = null) {

        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @param string $fileRelative
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testFile($fileRelative, &$report = null) {

        $file = _EPH_WEBSITE_DIR_ . DIRECTORY_SEPARATOR . $fileRelative;

        if (!file_exists($file)) {
            $report = 'File or directory ' . $file . ' does not exist.';
            return false;
        }

        if (!is_writable($file)) {
            $report = 'File or directory ' . $file . ' is not writable.';
            return false;
        }

        return true;
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testRootDir($dir, &$report = null) {

        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testLogDir($dir, &$report = null) {

        return static::testDir($dir, false, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testAdminDir($dir, &$report = null) {

        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testImgDir($dir, &$report = null) {

        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testPluginDir($dir, &$report = null) {

        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testCacheDir($dir, &$report = null) {

        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testToolsV2Dir($dir, &$report = null) {

        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testCacheV2Dir($dir, &$report = null) {

        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testDownloadDir($dir, &$report = null) {

        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testMailsDir($dir, &$report = null) {

        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testTranslationsDir($dir, &$report = null) {

        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testThemeLangDir($dir, &$report = null) {

        $absoluteDir = rtrim(_EPH_WEBSITE_DIR_, '\\/') . DIRECTORY_SEPARATOR . trim($dir, '\\/');

        if (!file_exists($absoluteDir)) {
            return false;
        }

        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testThemePdfLangDir($dir, &$report = null) {

        $absoluteDir = rtrim(_EPH_WEBSITE_DIR_, '\\/') . DIRECTORY_SEPARATOR . trim($dir, '\\/');

        if (!file_exists($absoluteDir)) {
            return true;
        }

        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testThemeCacheDir($dir, &$report = null) {

        $absoluteDir = rtrim(_EPH_WEBSITE_DIR_, '\\/') . DIRECTORY_SEPARATOR . trim($dir, '\\/');

        if (!file_exists($absoluteDir)) {
            return true;
        }

        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testCustomizableProductsDir($dir, &$report = null) {

        return static::testDir($dir, false, $report);
    }

    /**
     * @param $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testVirtualProductsDir($dir, &$report = null) {

        return static::testDir($dir, false, $report);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testMbstring() {

        return extension_loaded('mbstring');
    }

    /**
     * @return bool
     *
     * @since 1.1.0
     */
    public static function testOpenSSL() {

        return extension_loaded('openssl')
        && function_exists('openssl_encrypt');
    }

    /**
     * @return bool
     *
     * @deprecated 1.0.8
     * @deprecated since PHP 7.1
     */
    public static function testMcrypt() {

        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testSessions() {

        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testDom() {

        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * Test the set of files defined above. Not used by the installer, but by
     * AdminInformationController.
     *
     * @param bool $full
     *
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testFiles($full = false) {

        
        $return = [];

        foreach (static::$testFiles as $file) {
           
            if (!file_exists(rtrim(_EPH_WEBSITE_DIR_, DIRECTORY_SEPARATOR) . str_replace('/', DIRECTORY_SEPARATOR, $file))) {

                if ($full) {
                    array_push($return, $file);
                } else {
                    return false;
                }

            }

        }

        if ($full) {
            return $return;
        }

        return true;
    }

}
