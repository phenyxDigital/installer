<?php
#[AllowDynamicProperties]
/**
 * Class UserCore
 *
 * @since 2.1.0.0
 */
class User extends PhenyxObjectModel {

	protected static $instance;
	// @codingStandardsIgnoreStart
	/**
	 * @see PhenyxObjectModel::$definition
	 */
	public static $definition = [
		'table'   => 'user',
		'primary' => 'id_user',
		'fields'  => [
			'id_country'                 => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
			'id_gender'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'id_default_group'           => ['type' => self::TYPE_INT, 'copy_post' => false],
			'id_lang'                    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
			'customer_code'              => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'nickname'                   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'company'                    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'siret'                      => ['type' => self::TYPE_STRING, 'validate' => 'isSiret'],
			'ape'                        => ['type' => self::TYPE_STRING, 'validate' => 'isApe'],
			'vat_number'                 => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'firstname'                  => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32],
			'lastname'                   => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32],
			'email'                      => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 128],
			'passwd'                     => ['type' => self::TYPE_STRING, 'validate' => 'isPasswd', 'required' => true, 'size' => 60],
			'password'                   => ['type' => self::TYPE_STRING],
			'last_passwd_gen'            => ['type' => self::TYPE_STRING, 'copy_post' => false],
			'birthday'                   => ['type' => self::TYPE_DATE],
			'newsletter'                 => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'ip_registration_newsletter' => ['type' => self::TYPE_STRING, 'copy_post' => false],
			'newsletter_date_add'        => ['type' => self::TYPE_DATE, 'copy_post' => false],
			'optin'                      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'website'                    => ['type' => self::TYPE_STRING, 'validate' => 'isUrl'],
			'secure_key'                 => ['type' => self::TYPE_STRING, 'validate' => 'isMd5', 'copy_post' => false],
			'note'                       => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'copy_post' => false, 'size' => 65000],
			'id_theme'                   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
            'avatar'                 => ['type' => self::TYPE_STRING],
            'id_timezone' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'is_guest'                     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
			'active'                     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
			'deleted'                    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
			'is_admin'                   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],	
            'last_connection_date'       => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbDefault' => '1970-01-01'],
			'date_add'                   => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
			'date_upd'                   => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
		],
	];
	protected static $_defaultGroupId = [];
	protected static $_userHasAddress = [];
	protected static $_user_groups = [];
	/** @var string Secure key */
	public $secure_key;
	/** @var string protected note */
	public $note;

	public $id_theme = 1;
    
    
	/** @var int Gender ID */
	public $id_gender = 0;
	/** @var int Default group ID */
	public $id_default_group;
	/** @var int Current language used by the user */
	public $id_lang;
	/** @var int Current Country of the user */
	public $id_country;
	/** @var string User Code */
	public $customer_code;
    
    public $nickname;
	/** @var string Lastname */
	public $lastname;
	/** @var string Firstname */
	public $firstname;
	/** @var string Birthday (yyyy-mm-dd) */
	public $birthday = null;
	/** @var string e-mail */
	public $email;
	/** @var bool Newsletter subscription */
	public $newsletter;
	/** @var string Newsletter ip registration */
	public $ip_registration_newsletter;
	/** @var string Newsletter ip registration */
	public $newsletter_date_add;
	/** @var bool Opt-in subscription */
	public $optin;
	/** @var string WebSite * */
	public $website;
	/** @var string Company */
	public $company;
	/** @var string SIRET */
	public $siret;
	/** @var string APE */
	public $ape;
	/** @var string VAT number */
	public $vat_number;

	public $id_guest;

	/** @var int Password */
	public $passwd;

	public $password;
	/** @var string Datetime Password */
	public $last_passwd_gen;
    
    public $avatar;
    
    public $id_timezone;
	/** @var bool Status */
	public $active = true;

	/** @var bool True if carrier has been deleted (staying in database as deleted) */
	public $deleted = 0;
	/** @var string Object creation date */
	public $date_add;
	/** @var string Object last modification date */
	public $date_upd;
	public $years;
	public $days;
	public $months;
	/** @var int user id_country as determined by geolocation */
	public $geoloc_id_country;
	/** @var int user id_state as determined by geolocation */
	public $geoloc_id_state;
	/** @var string user postcode as determined by geolocation */
	public $geoloc_postcode;
	/** @var bool is the user logged in */
	public $logged = 0;
    
    public $address1;

	public $address2;

	public $postcode;

	public $city;

	public $phone_mobile;

	public $title;
    
    public $is_guest = 0;
    

	public $is_admin;
    
    public $date_format;

	
	

	public $last_connection_date;
	public $groupBox;

	public function __construct($id = null) {

		$this->id_default_group = (int) Configuration::get('EPH_CUSTOMER_GROUP');

		parent::__construct($id);
        
        if ($this->id) {
            $this->id_guest = $this->getIdGuest();
			$id_address = Address::getFirstCustomerAddressId($this->id);
			$address = new Address((int) $id_address);
			$this->phone_mobile = $address->phone_mobile;
			$this->address1 = $address->address1;
			$this->address2 = $address->address2;
			$this->postcode = $address->postcode;
			$this->city = $address->city;
			$this->title = $this->getTitle();
            $this->date_format = $this->getDateFormat();
            
		}

	}
    
    public static function construct($className,$id, $id_lang = null) {
        
        
        $context = Context::getContext();
        $objectData = parent::construct($className,$id, $id_lang);
        $objectData['id_guest'] = $this->getStaticIdGuest($id);
        $id_address = Address::getFirstCustomerAddressId($id);
        if($id_address > 0) {
            
            $address = Address::construct('Address', (int) $id_address);  
            $objectData['phone_mobile'] = $address->phone_mobile;
			$objectData['address1'] = $address->address1;
			$objectData['address2'] = $address->address2;
			$objectData['postcode'] = $address->postcode;
			$objectData['city'] = $address->city;
			$this->title = User::getStaticTitle($objectData['id_gender']);
        }
       
        return Tools::jsonDecode(Tools::jsonEncode($objectData));
    }    
    
    public function getDateFormat() {
        
        if($this->id_lang > 0) {
            return Db::getInstance()->getValue(
			     (new DbQuery())
				->select('`date_format_full`')
				->from('lang')
				->where('`id_lang` = ' .$this->id_lang)
		  );
        }
        return 'm/d/Y H:i:s';
    }
    
    public function getTitle() {

		$context = Context::getContext();
		return Db::getInstance()->getValue(
			(new DbQuery())
				->select('`name`')
				->from('gender_lang')
				->where('`id_lang` = ' . Configuration::get('EPH_LANG_DEFAULT'))
				->where('`id_gender` = ' . $this->id_gender)
		);
	}
    
    public static function getStaticTitle($id_gender) {

		$context = Context::getContext();
		return Db::getInstance()->getValue(
			(new DbQuery())
				->select('`name`')
				->from('gender_lang')
				->where('`id_lang` = ' . Configuration::get('EPH_LANG_DEFAULT'))
				->where('`id_gender` = ' . $id_gender)
		);
	}
    
    public function getIdGuest() {

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('id_guest')
				->from('guest')
				->where('`id_user` = ' . $this->id)
		);

	}

	public static function getStaticIdGuest($id) {

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('id_guest')
				->from('guest')
				->where('`id_user` = ' . $id)
		);

	}

	public static function getInstance() {

		if (!User::$instance) {
			User::$instance = new User();
		}

		return User::$instance;
	}

	
    
    public function getParamFields() {
        
        $genders = [
			[
				'title'     => $this->l('Mister'),
				'id_gender' => 1,
			],
			[
				'title'     => $this->l('Miss'),
				'id_gender' => 2,
			],
		];

		$this->paramFields = [
			[
				'title'      => $this->l('ID'),
				'width'      => 50,
				'dataIndx'   => 'id_user',
				'dataType'   => 'integer',
				'editable'   => false,
				'hiddenable' => 'no',
				'align'      => 'center',
				'valign'     => 'center',
				'filter'     => [
					'crules' => [['condition' => "contain"]],
				],
			],

			[
				'title'      => $this->l('Customer Code'),
				'width'      => 100,
				'dataIndx'   => 'customer_code',
				'dataType'   => 'string',
				'align'      => 'left',
				'editable'   => false,
				'hidden'     => false,
				'hiddenable' => 'yes',
				'valign'     => 'center',
				'filter'     => [

					'crules' => [['condition' => "contain"]],
				],

			],
			[
				'title'      => $this->l('Nick name'),
				'width'      => 100,
				'dataIndx'   => 'nickname',
				'dataType'   => 'string',
				'align'      => 'left',
				'editable'   => false,
				'hidden'     => false,
				'hiddenable' => 'yes',
				'valign'     => 'center',
				'filter'     => [

					'crules' => [['condition' => "contain"]],
				],

			],

			[
				'title'    => $this->l('Social title'),
				'width'    => 75,
				'dataIndx' => 'title',
				'align'    => 'center',
				'dataType' => 'string',
				'cls'      => 'pq-dropdown',
				'editor'   => [
					'type'      => "select",
					'valueIndx' => "id_gender",
					'labelIndx' => "title",
					'options'   => $genders,

				],

			],
			[
				'title'    => $this->l('Firstname'),
				'width'    => 150,
				'dataIndx' => 'firstname',
				'align'    => 'left',
				'valign'   => 'center',
				'editable' => true,
				'dataType' => 'string',
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],
				'editable' => true,
			],
			[
				'title'    => $this->l('Name'),
				'width'    => 150,
				'dataIndx' => 'lastname',
				'dataType' => 'string',
				'editable' => true,
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],
				'editable' => true,
			],
			[
				'title'    => $this->l('Email'),
				'width'    => 150,
				'dataIndx' => 'email',
				'dataType' => 'string',
				'cls'      => 'jsCopyClipBoard ',
				'editable' => false,
				'hidden'   => false,
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],

			],
			[
				'title'    => $this->l('Company'),
				'width'    => 100,
				'dataIndx' => 'company',
				'dataType' => 'string',
				'filter'   => [
					'crules' => [['condition' => "contain"]],

				],
			],

			[
				'title'     => $this->l('Customer group'),
				'width'     => 100,
				'dataIndx'  => 'tarif',
				'labelIndx' => 'id_default_group',
				'dataType'  => 'string',

			],
			[
				'title'    => $this->l('Phone'),
				'width'    => 150,
				'dataIndx' => 'phone_mobile',
				'cls'      => 'jsCopyClipBoard telephone',
				'align'    => 'left',
				'dataType' => 'string',
				'editable' => true,
				'filter'   => [
					'crules' => [['condition' => "contain"]],

				],

			],

			[
				'title'    => $this->l('Address'),
				'width'    => 150,
				'dataIndx' => 'address1',
				'align'    => 'left',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,
			],
			[
				'title'    => $this->l('Address (follow)'),
				'width'    => 150,
				'dataIndx' => 'address2',
				'align'    => 'left',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,
			],

			[
				'title'    => $this->l('Postcode'),
				'width'    => 150,
				'dataIndx' => 'postcode',
				'align'    => 'left',
				'dataType' => 'string',
				'editable' => false,

			],
			[
				'title'    => $this->l('City'),
				'width'    => 150,
				'dataIndx' => 'city',
				'align'    => 'left',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],
			],
			[
				'title'    => $this->l('Country'),
				'width'    => 150,
				'dataIndx' => 'country',
				'align'    => 'left',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],
			],
			[
				'title'    => $this->l('Birthday'),

				'dataIndx' => 'birthday',
				'minWidth' => 150,
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'date',
				'format'   => 'dd/mm/yy',
				'session'  => false,
				'vdi'      => true,
				'editable' => true,
				'hidden'   => false,
				'cls'      => 'pq-calendar pq-side-icon',
				'editor'   => [
					'type'    => "textbox",
					'init'    => 'dateEditor',
					'getData' => 'getDataDate',
				],
				'render'   => 'renderBirthDate',
			],

			[
				'title'    => $this->l('Registration date'),
				'width'    => 150,
				'dataIndx' => 'date_add',
				'cls'      => 'rangeDate',
				'align'    => 'center',
				'dataType' => 'date',
				'format'   => 'dd/mm/yy',
				'editable' => false,

			],

			[

				'dataIndx'   => 'user_admin_profile',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[

				'dataIndx'   => 'is_admin',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[

				'dataIndx'   => 'id_default_group',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],

		];
        
        return parent::getParamFields();

	}

    
    public function add($autoDate = true, $nullValues = true) {

		$this->id_lang = ($this->id_lang) ? $this->id_lang : Context::getContext()->language->id;
		$this->birthday = (empty($this->years) ? $this->birthday : (int) $this->years . '-' . (int) $this->months . '-' . (int) $this->days);
		$this->secure_key = md5(uniqid(rand(), true));
		$this->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-' . Configuration::get('EPH_PASSWD_TIME_FRONT') . 'minutes'));
        $timeZone = date_default_timezone_get();
        $this->id_timezone = TimeZone::getByName($timeZone);

		if ($this->newsletter && !Validate::isDate($this->newsletter_date_add)) {
			$this->newsletter_date_add = date('Y-m-d H:i:s');
		}
        $user = Hook::exec('actionAddUserBefore', ['user' => $this], null, false, false, true);
        if(is_object($user) && Validate::isLoadedObject($user)) {
            foreach ($user as $key => $value) {
                if (property_exists($this, $key) && $key != 'id_user') {
                    $this->{$key} = $value;
                }
            }
            
        }
		$success = parent::add($autoDate, $nullValues);
        if($success) {
             Hook::exec('actionAddUserAfter', ['user' => $this]);
		     $this->updateGroup($this->groupBox);
        }
       
        
        
		return $success;
	}
    
    public function update($nullValues = false) {

		if ($this->newsletter && !Validate::isDate($this->newsletter_date_add)) {
			$this->newsletter_date_add = date('Y-m-d H:i:s');
		}

		if (isset(Context::getContext()->controller) && Context::getContext()->controller->controller_type == 'admin') {
			$this->updateGroup($this->groupBox);
		}
        if(empty($this->id_timezone)) {
            
            $timeZone = date_default_timezone_get();
            $this->id_timezone = TimeZone::getByName($timeZone);
        }

		if ($this->deleted) {
			$addresses = $this->getAddresses((int) $this->context->language->id);

			foreach ($addresses as $address) {
				$obj = new Address((int) $address['id_address']);
				$obj->delete();
			}

		}
        
        $result = parent::update(true);
        
        
		return $result;
	}

    public function delete($refresh = true) {

		$addresses = $this->getAddresses((int) Context::getContext()->language->id);

		foreach ($addresses as $address) {
			$obj = new Address((int) $address['id_address']);
			$obj->delete();
		}

		Db::getInstance()->execute(
			(new DbQuery())
				->type('DELETE')
				->from('customer_group')
				->where('`id_user` = ' . (int) $this->id)
		);
		Db::getInstance()->execute(
			(new DbQuery())
				->type('DELETE')
				->from('message')
				->where('`id_user` = ' . (int) $this->id)
		);

		$cts = Db::getInstance()->executes(
			(new DbQuery())
				->select('id_customer_thread')
				->from('customer_thread')
				->where('`id_user` = ' . (int) $this->id)
		);

		if ($cts) {

			foreach ($cts as $ct) {
				Db::getInstance()->execute(
					(new DbQuery())
						->type('DELETE')
						->from('customer_thread')
						->where('id_customer_thread=' . (int) $ct['id_customer_thread'])
				);
				Db::getInstance()->execute(
					(new DbQuery())
						->type('DELETE')
						->from('customer_message')
						->where('id_customer_thread = ' . (int) $ct['id_customer_thread'])
				);

			}

		}
        
        $result = parent::delete();
        if($result) {
            Hook::exec('actionDeleteUserAfter', ['user' => $this]);
        }
        
        
		return $result;
	}



	public function getTheme() {

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('directory')
				->from('theme')
				->where('`id_theme` = ' . $this->id_theme)
		);
	}

	public static function generateUserCode($id_country, $postcode = null) {

		$cc = Db::getInstance()->getValue('SELECT `id_user` FROM `' . _DB_PREFIX_ . 'user` ORDER BY `id_user` DESC') + 1;

		if (isset($postcode)) {

			if ($id_country != 8) {
				$iso_code = Db::getInstance()->getValue('SELECT `iso_code` FROM `' . _DB_PREFIX_ . 'country` WHERE `id_country`= ' . $id_country . '');
			} else {
				$iso_code = substr($postcode, 0, 2);

				if ($iso_code >= 97) {
					$iso_code = Db::getInstance()->getValue('SELECT `iso_code` FROM `' . _DB_PREFIX_ . 'country` WHERE `id_country`= ' . $id_country . '');
				}

			}

			$Shop_iso = 'WB';
			return substr($postcode, 0, 2) . $Shop_iso . sprintf("%04s", $cc);
		} else {
			$iso_code = Db::getInstance()->getValue('SELECT `iso_code` FROM `' . _DB_PREFIX_ . 'country` WHERE `id_country`= ' . $id_country . '');

			$Shop_iso = 'WB_' . $iso_code;

			return $Shop_iso . sprintf("%04s", $cc);
		}

	}

	public static function getUsers($onlyActive = null) {

		$sql = new DbQuery();
		$sql->select('`id_user`, `email`, `firstname`, `lastname`');
		$sql->from(bqSQL(static::$definition['table']));
		$sql->where('1 ');

		if ($onlyActive) {
			$sql->where('`active` = 1');
		}

		$sql->orderBy('`id_user` ASC');

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);
	}

	public static function getUsersByEmail($email) {

		$sql = new DbQuery();
		$sql->select('*');
		$sql->from(bqSQL(static::$definition['table']));
		$sql->where('`email` = \'' . pSQL($email) . '\' OR `nickname` = \'' . pSQL($email) . '\'');

		return Db::getInstance()->executeS($sql);
	}

	public static function isBanned($idUser) {

		if (!Validate::isUnsignedId($idUser)) {
			return true;
		}

		$cacheId = 'User::isBanned_' . (int) $idUser;

		if (!Cache::isStored($cacheId)) {
			$sql = new DbQuery();
			$sql->select('`id_user`');
			$sql->from(bqSQL(static::$definition['table']));
			$sql->where('`id_user` = ' . (int) $idUser);
			$sql->where('`active` = 1');
			$sql->where('`deleted` = 0');
			$result = (bool) !Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow($sql);
			Cache::store($cacheId, $result);

			return $result;
		}

		return Cache::retrieve($cacheId);
	}

	public static function userExists($email, $returnId = false, $ignoreGuest = true) {

		$sql = new DbQuery();
		$sql->select('`id_user`');
		$sql->from(bqSQL(static::$definition['table']));
		$sql->where('`email` = \'' . pSQL($email) . '\' OR `nickname` = \'' . pSQL($email) . '\'');

		$result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($sql);

		return ($returnId ? (int) $result : (bool) $result);
	}

	public static function customerHasAddress($idUser, $idAddress) {

		$key = (int) $idUser . '-' . (int) $idAddress;

		if (!array_key_exists($key, static::$_userHasAddress)) {
			static::$_userHasAddress[$key] = (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
				'
			SELECT `id_address`
			FROM `' . _DB_PREFIX_ . 'address`
			WHERE `id_user` = ' . (int) $idUser . '
			AND `id_address` = ' . (int) $idAddress . '
			AND `deleted` = 0'
			);
		}

		return static::$_userHasAddress[$key];
	}

	public static function resetAddressCache($idUser, $idAddress) {

		$key = (int) $idUser . '-' . (int) $idAddress;

		if (array_key_exists($key, static::$_userHasAddress)) {
			unset(static::$_userHasAddress[$key]);
		}

	}

	public static function getAddressesTotalById($idUser) {

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			'
			SELECT COUNT(`id_address`)
			FROM `' . _DB_PREFIX_ . 'address`
			WHERE `id_user` = ' . (int) $idUser . '
			AND `deleted` = 0'
		);
	}

	public static function searchByName($query, $limit = null) {

		$sqlBase = 'SELECT *
				FROM `' . _DB_PREFIX_ . 'user`';
		$sql = '(' . $sqlBase . ' WHERE `email` LIKE \'%' . pSQL($query) . '%\')';
		$sql .= ' UNION (' . $sqlBase . ' WHERE `id_user` = ' . (int) $query . ')';
		$sql .= ' UNION (' . $sqlBase . ' WHERE `lastname` LIKE \'%' . pSQL($query) . '%\')';
		$sql .= ' UNION (' . $sqlBase . ' WHERE `firstname` LIKE \'%' . pSQL($query) . '%\')';
		$sql .= ' UNION (' . $sqlBase . ' WHERE `customer_code` LIKE \'%' . pSQL($query) . '%\')';

		if ($limit) {
			$sql .= ' LIMIT 0, ' . (int) $limit;
		}

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);
	}

	public static function searchByIp($ip) {

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			'
		SELECT DISTINCT c.*
		FROM `' . _DB_PREFIX_ . 'user` c
		LEFT JOIN `' . _DB_PREFIX_ . 'guest` g ON g.id_user = c.id_user
		LEFT JOIN `' . _DB_PREFIX_ . 'connections` co ON g.id_guest = c.id_user
		WHERE co.`ip_address` = \'' . (int) ip2long(trim($ip)) . '\''
		);
	}

	public static function getDefaultGroupId($idUser) {

		if (!Group::isFeatureActive()) {
			static $psUserGroup = null;

			if ($psUserGroup === null) {
				$psUserGroup = Configuration::get('EPH_CUSTOMER_GROUP');
			}

			return $psUserGroup;
		}

		if (!isset(static::$_defaultGroupId[(int) $idUser])) {
			static::$_defaultGroupId[(int) $idUser] = Db::getInstance()->getValue(
				'
				SELECT `id_default_group`
				FROM `' . _DB_PREFIX_ . 'user`
				WHERE `id_user` = ' . (int) $idUser
			);
		}

		return static::$_defaultGroupId[(int) $idUser];
	}

	
	public function updateGroup($list) {

		if ($list && !empty($list)) {
			$this->cleanGroups();
			$this->addGroups($list);
		} else {
			$this->addGroups([$this->id_default_group]);
		}

	}

	/**
	 * @return bool
	 *
	 * @since 2.1.0.0
	 * @throws PhenyxDatabaseExceptionException
	 */
	public function cleanGroups() {

		return Db::getInstance()->delete('customer_group', 'id_user = ' . (int) $this->id);
	}

	/**
	 * @param $groups
	 *
	 * @throws PhenyxDatabaseExceptionException
	 * @throws PhenyxException
	 * @since 2.1.0.0
	 */
	public function addGroups($groups) {

		foreach ($groups as $group) {
			$row = ['id_user' => (int) $this->id, 'id_group' => (int) $group];
			Db::getInstance()->insert('customer_group', $row, false, true, Db::INSERT_IGNORE);
		}

	}

	
	public function getAddresses($idLang) {

		$cacheId = 'User::getAddresses' . (int) $this->id . '-' . (int) $idLang;

		if (!Cache::isStored($cacheId)) {
			$result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
				(new DbQuery())
					->select('DISTINCT a.*, cl.`name` AS `country`, s.`name` AS `state`, s.`iso_code` AS `state_iso`')
					->from('address', 'a')
					->leftJoin('country', 'c', 'a.`id_country` = c.`id_country`')
					->leftJoin('country_lang', 'cl', 'c.`id_country` = cl.`id_country` AND cl.`id_lang` = ' . (int) $idLang)
					->leftJoin('state', 's', 's.`id_state` = a.`id_state`')
					->where('a.`id_user` = ' . (int) $this->id)
					->where('a.`deleted` = 0')
			);
			Cache::store($cacheId, $result);

			return $result;
		}

		return Cache::retrieve($cacheId);
	}

	/**
	 * Return user instance from its e-mail (optionally check password)
	 *
	 * @param string $email             E-mail
	 * @param string $plainTextPassword Password is also checked if specified
	 * @param bool   $ignoreGuest
	 *
	 * @return User|bool
	 *
	 * @throws PhenyxDatabaseExceptionException
	 * @throws PhenyxException
	 * @since 2.1.0.0
	 */
	public function getByEmail($email, $plainTextPassword = null, $ignoreGuest = true) {

        
		if ((!Validate::isEmail($email) && !Validate::isGenericName($email)) && ($plainTextPassword && !Validate::isPasswd($plainTextPassword))) {
			die(Tools::displayError());
		}

		$sql = new DbQuery();
		$sql->select('*');
		$sql->from(bqSQL(static::$definition['table']));
		$sql->where('`email` = \'' . pSQL($email) . '\' OR `nickname` = \'' . pSQL($email) . '\'');
		$sql->where('`deleted` = 0');
       
		$result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow($sql);

		if (!$result) {
			return false;
		}

		// If password is provided but doesn't match.
		if ($plainTextPassword && !password_verify($plainTextPassword, $result['passwd'])) {
			// Check if it matches the legacy md5 hashing and, if it does, rehash it.
            
			if (Validate::isMd5($result['passwd']) && $result['passwd'] === md5(_COOKIE_KEY_ . $plainTextPassword)) {
				$newHash = Tools::hash($plainTextPassword);
				Db::getInstance()->update(
					bqSQL(static::$definition['table']),
					[
						'passwd' => pSQL($newHash),
					],
					'`id_user` = ' . (int) $result['id_user']
				);
				$result['passwd'] = $newHash;
			} else {
				return false;
			}

		}
        
		$this->id = $result['id_user'];

		foreach ($result as $key => $value) {

			if (property_exists($this, $key)) {
				$this->{$key}

				= $value;
			}

		}

		return $this;
	}

	public function getLastEmails() {

		if (!$this->id) {
			return [];
		}

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('m.*, l.`name` as `language`')
				->from('mail', 'm')
				->leftJoin('lang', 'l', 'm.`id_lang` = l.`id_lang`')
				->where('`recipient` = \'' . pSQL($this->email) . '\'')
				->orderBy('m.`date_add` DESC')
				->limit(10)
		);
	}

	public function getLastConnections() {

		if (!$this->id) {
			return [];
		}

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('c.`id_connections`, c.`date_add`, COUNT(cp.`id_page`) AS `pages`')
				->select('TIMEDIFF(MAX(cp.time_end), c.date_add) AS time, http_referer,INET_NTOA(ip_address) AS ipaddress')
				->from('guest', 'g')
				->leftJoin('connections', 'c', 'c.`id_guest` = g.`id_user`')
				->leftJoin('connections_page', 'cp', 'c.`id_connections` = cp.`id_connections`')
				->where('g.`id_user` = ' . (int) $this->id)
				->groupBy('c.`id_connections`')
				->orderBy('c.`date_add` DESC')
				->limit(10)
		);
	}

	public function customerIdExists($idUser) {

		return User::userIdExistsStatic((int) $idUser);
	}

	public static function userIdExistsStatic($idUser) {

		$cacheId = 'User::userIdExistsStatic' . (int) $idUser;

		if (!Cache::isStored($cacheId)) {
			$result = (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
				(new DbQuery())
					->select('`id_user`')
					->from('user', 'c')
					->where('c.`id_user` = ' . (int) $idUser)
			);
			Cache::store($cacheId, $result);

			return $result;
		}

		return Cache::retrieve($cacheId);
	}

	public function getGroups() {

		return User::getGroupsStatic((int) $this->id);
	}

	public static function getGroupsStatic($idUser) {

		if (!Group::isFeatureActive()) {
			return [Configuration::get('EPH_CUSTOMER_GROUP')];
		}

		// @codingStandardsIgnoreStart

		if ($idUser == 0) {
			static::$_user_groups[$idUser] = [(int) Configuration::get('EPH_UNIDENTIFIED_GROUP')];
		}

		if (!isset(static::$_user_groups[$idUser])) {
			static::$_user_groups[$idUser] = [];
			$result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
				(new DbQuery())
					->select('cg.`id_group`')
					->from('customer_group', 'cg')
					->where('cg.`id_user` = ' . (int) $idUser)
			);

			foreach ($result as $group) {
				static::$_user_groups[$idUser][] = (int) $group['id_group'];
			}

		}

		return static::$_user_groups[$idUser];
		// @codingStandardsIgnoreEnd
	}

	public function toggleStatus() {

		parent::toggleStatus();

		/* Change status to active/inactive */

		return Db::getInstance()->update(
			bqSQL(static::$definition['table']),
			[
				'date_upd' => ['type' => 'sql', 'value' => 'NOW()'],
			],
			'`' . bqSQL(static::$definition['primary']) . '` = ' . (int) $this->id
		);
	}

	
	public function isLogged($withGuest = false) {
        
        if (!$withGuest && $this->is_guest == 1) {
			return false;
		}

		return ($this->logged == 1 && $this->id && Validate::isUnsignedId($this->id) && User::checkPassword($this->id, $this->passwd));
	}

	public static function checkPassword($idUser, $plaintextOrHashedPassword) {

		if (!Validate::isUnsignedId($idUser)) {
			die(Tools::displayError());
		}

		if (Validate::isMd5($plaintextOrHashedPassword) || mb_substr($plaintextOrHashedPassword, 0, 4) === '$2y$') {
			$hashedPassword = $plaintextOrHashedPassword;

			return static::checkPasswordInDatabase($idUser, $hashedPassword);
		} else {
			$hashedPassword = Tools::encrypt($plaintextOrHashedPassword);

			if (static::checkPasswordInDatabase($idUser, $hashedPassword)) {
				return true;
			}

			$sql = new DbQuery();
			$sql->select('`passwd`');
			$sql->from(bqSQL(static::$definition['table']));
			$sql->where('`id_user` = ' . (int) $idUser);

			$hashedPassword = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($sql);

			return password_verify($plaintextOrHashedPassword, $hashedPassword);
		}

	}

	protected static function checkPasswordInDatabase($idUser, $hashedPassword) {

		$cacheId = 'User::checkPassword' . (int) $idUser . '-' . $hashedPassword;

		if (!Cache::isStored($cacheId)) {
			$sql = new DbQuery();
			$sql->select('`id_user`');
			$sql->from(bqSQL(static::$definition['table']));
			$sql->where('`id_user` = ' . (int) $idUser);
			$sql->where('`passwd` = \'' . pSQL($hashedPassword) . '\'');
			$result = (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($sql);
			Cache::store($cacheId, $result);

			return $result;
		}

		return Cache::retrieve($cacheId);
	}

	public function logout() {

		Hook::exec('actionUserLogoutBefore', ['user' => $this]);

		if (isset(Context::getContext()->cookie)) {
			Context::getContext()->cookie->logout();
		}

		$this->logged = 0;

		Hook::exec('actionUserLogoutAfter', ['user' => $this]);
	}

	public function mylogout() {

		Hook::exec('actionUserLogoutBefore', ['user' => $this]);

		if (isset(Context::getContext()->cookie)) {
			Context::getContext()->cookie->mylogout();
		}

		$this->logged = 0;

		Hook::exec('actionUserLogoutAfter', ['user' => $this]);
	}
    
    public function getAutoCompleteCustomer() {
        
        if($this->context->cache_enable && is_object($this->context->cache_api)) {
           $value = $this->context->cache_api->getData('autoCompleteCustomer', 864000);
           $temp = empty($value) ? null : Tools::jsonDecode($value, true);
           if(!empty($temp)) {
               return $temp;
           }            
        }
        
        $customers = Db::getInstance()->executeS(
            (new DbQuery())
            ->select('u.`id_user`, u.`customer_code`, u.`firstname`, u.`lastname`, u.`company`, ad.address1, ad.postcode, ad.city, ad.phone_mobile')
			->from('user', 'u')
            ->join('LEFT JOIN (SELECT id_user, address1, postcode, city, phone_mobile FROM `' . _DB_PREFIX_ . 'address` GROUP BY id_user) ad ON ad.id_user = u.id_user')
            ->where('u.`active` = 1')
			->orderBy('u.id_user DESC')
        );
        
        if($this->context->cache_enable && is_object($this->context->cache_api)) {
            $temp = $customers === null ? null : Tools::jsonEncode($customers);
            $this->context->cache_api->putData('autoCompleteCustomer', $temp);
            
        }
        
        return $customers;
        
	}

}
