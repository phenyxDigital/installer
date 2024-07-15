<?php

/**
 * Class CompanyUrl
 *
 * @since 1.9.1.0
 */
class CompanyUrl extends PhenyxObjectModel {

   
    public $require_context = false;
    
    public $id_company;
    
    public $domain;
    
    public $domain_ssl;

    public $admin_ssl;
    
    public $physical_uri;
    
    public $virtual_uri;
    
    public $main;
    
    public $active;
    
    protected static $main_domain = [];
    
    protected static $main_domain_ssl = [];

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'company_url',
        'primary' => 'id_company_url',
        'fields'  => [
            'active'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'main'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'domain'       => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 255],
            'domain_ssl'   => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255],
            'id_company'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'physical_uri' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 64],
            'virtual_uri'  => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 64],
        ],
    ];

    public function getFields() {

        $this->domain = trim($this->domain);
        $this->domain_ssl = trim($this->domain_ssl);
        $this->physical_uri = trim(str_replace(' ', '', $this->physical_uri), '/');

        if ($this->physical_uri) {
            $this->physical_uri = preg_replace('#/+#', '/', '/' . $this->physical_uri . '/');
        } else {
            $this->physical_uri = '/';
        }

        $this->virtual_uri = trim(str_replace(' ', '', $this->virtual_uri), '/');

        if ($this->virtual_uri) {
            $this->virtual_uri = preg_replace('#/+#', '/', trim($this->virtual_uri, '/')) . '/';
        }

        return parent::getFields();
    }

    public function getBaseURI() {

        return $this->physical_uri . $this->virtual_uri;
    }

    public function getURL($ssl = false) {

        if (!$this->id) {
            return null;
        }

        if (defined('_EPH_ROOT_DIR_')) {
            $url = ($ssl) ? 'https://' . $this->admin_ssl : 'http://' . $this->admin_ssl;
        } else {
            $url = ($ssl) ? 'https://' . $this->domain_ssl : 'http://' . $this->domain;
        }

        return $url . $this->getBaseUri();
    }

    public static function getCompanyUrls($idCompany = false) {

        $urls = new PhenyxCollection('CompanyUrl');

        if ($idCompany) {
            $urls->where('id_company', '=', $idCompany);
        }

        return $urls;
    }

    public function setMain() {

        $res = Db::getInstance()->update('company_url', ['main' => 0], 'id_company = ' . (int) $this->id_company);
        $res &= Db::getInstance()->update('company_url', ['main' => 1], 'id_company_url = ' . (int) $this->id);
        $this->main = true;

        $sql = 'SELECT s1.id_company_url FROM ' . _DB_PREFIX_ . 'company_url s1
                WHERE (
                    SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'company_url s2
                    WHERE s2.main = 1
                    AND s2.id_company = s1.id_company
                ) = 0
                GROUP BY s1.id_company';

        foreach (Db::getInstance()->executeS($sql) as $row) {
            Db::getInstance()->update('company_url', ['main' => 1], 'id_company_url = ' . $row['id_company_url']);
        }

        return $res;
    }

    public function canAddThisUrl($domain, $domainSsl, $physicalUri, $virtualUri) {

        $physicalUri = trim($physicalUri, '/');

        if ($physicalUri) {
            $physicalUri = preg_replace('#/+#', '/', '/' . $physicalUri . '/');
        } else {
            $physicalUri = '/';
        }

        $virtualUri = trim($virtualUri, '/');

        if ($virtualUri) {
            $virtualUri = preg_replace('#/+#', '/', trim($virtualUri, '/')) . '/';
        }

        if (defined('_EPH_ROOT_DIR_')) {
            return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_company_url`')
                    ->from('company_url')
                    ->where('`physical_uri` = \'' . pSQL($physicalUri) . '\'')
                    ->where('`virtual_uri` = \'' . pSQL($virtualUri) . '\'')
                    ->where($this->id ? '`id_company_url` != ' . (int) $this->id : '')
            );
        } else {
            return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_company_url`')
                    ->from('company_url')
                    ->where('`physical_uri` = \'' . pSQL($physicalUri) . '\'')
                    ->where('`virtual_uri` = \'' . pSQL($virtualUri) . '\'')
                    ->where('`domain` = \'' . pSQL($domain) . '\'' . (($domainSsl) ? ' OR domain_ssl = \'' . pSQL($domainSsl) . '\'' : ''))
                    ->where($this->id ? '`id_company_url` != ' . (int) $this->id : '')
            );
        }

    }

    public static function cacheMainDomainForCompany($idCompany) {

        // @codingStandardsIgnoreStart

        if (!isset(static::$main_domain_ssl[(int) $idCompany]) || !isset(static::$main_domain[(int) $idCompany])) {
            $row = Db::getInstance()->getRow(
                (new DbQuery())
                    ->select('`domain`, `domain_ssl`')
                    ->from('company_url')
                    ->where('`main` = 1')
                    ->where('`id_company` = ' . ($idCompany !== null ? (int) $idCompany : (int) Context::getContext()->company->id))
            );
            static::$main_domain[(int) $idCompany] = $row['domain'];
            static::$main_domain_ssl[(int) $idCompany] = $row['domain_ssl'];
        }

    }

    public static function resetMainDomainCache() {

        // @codingStandardsIgnoreStart
        static::$main_domain = [];
        static::$main_domain_ssl = [];
        // @codingStandardsIgnoreEnd
    }

    public static function getMainShopDomain($idCompany = null) {

        static::cacheMainDomainForCompany($idCompany);
        return static::$main_domain[(int) $idCompany];
    }

    public static function getMainShopDomainSSL($idCompany = null) {

        static::cacheMainDomainForCompany($idCompany);

        return static::$main_domain_ssl[(int) $idCompany];
    }

}
