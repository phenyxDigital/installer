<?php

/**
 * Class EmployeeCore
 *
 * @since 1.9.1.0
 */
class Employee extends User {
    
    public $id_profile = 0;
    
    public $id_last_customer_message;
    
    public $id_last_phenyx_notification;

	public $id_last_user;
    
    public $bo_theme = 'blacktie';
    
    public $bo_color;

    public $working_plan = '{"monday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"tuesday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"wednesday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"thursday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"friday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"saturday":null,"sunday":null}';
	public $workin_break;
	public $working_plan_exceptions;
    public $master_admin;
    
    public $is_manager;

    public function __construct($id = null) {
        
        self::$definition['fields']['id_profile'] = ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false];
        self::$definition['fields']['master_admin'] = ['type' => self::TYPE_BOOL];
        self::$definition['fields']['is_manager'] = ['type' => self::TYPE_BOOL];
        self::$definition['fields']['id_last_customer_message'] = ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'];
        self::$definition['fields']['id_last_phenyx_notification'] = ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'];
        self::$definition['fields']['id_last_user'] = ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'];
        self::$definition['fields']['bo_theme'] = ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32];
        self::$definition['fields']['bo_color'] = ['type' => self::TYPE_STRING];
        
        parent::__construct($id, null);

        if ($this->id) {
           	$this->working_plan = $this->getWorkingPlan();
			$this->workin_break = $this->getWorkingBreak();
			$this->working_plan_exceptions = $this->getWorkingPlanException();   
            
        }

        $this->image_dir = _EPH_EMPLOYEE_IMG_DIR_;
    }
    
    public function update($nullValues = false) {
		
        $result = parent::update($nullValues);
        
        if($result && $this->id_default_group == Configuration::get('EPH_ADMIN_DEFAULT_GROUP')) {
            Db::getInstance()->execute(
			     (new DbQuery())
				->type('DELETE')
				->from('employee_settings')
				->where('`id_employee` = ' . $this->id)
		    );
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'employee_settings`(`id_employee`, `working_plan`, `working_break`, `working_plan_exceptions`, `notifications`, `google_sync`, `google_token`, `google_calendar`, `sync_past_days`, `sync_future_days`, `calendar_view`) VALUES ('.$this->id.',\'' . $this->working_plan . '\',\'' . Tools::jsonEncode([]) . '\',NULL,1,NULL,NULL,NULL,30,90,\'DEFAULT\')';
        
            Db::getInstance()->execute($sql);
        }
        
        return $result;
        
        
        
	}
    
    public function delete($refresh = true) {

		$this->is_admin = 0;
        Db::getInstance()->execute(
			     (new DbQuery())
				->type('DELETE')
				->from('employee_settings')
				->where('`id_employee` = ' . $this->id)
		    );
        $this->update();

		return true;
	}
    
    public function getWorkingPlan() {
		
		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('working_plan')
				->from('employee_settings')
				->where('`id_employee` = ' . $this->id)
		);
	}
	public function getWorkingBreak() {
		
		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('working_break')
				->from('employee_settings')
				->where('`id_employee` = ' . $this->id)
		);
	}
	
	public function getWorkingPlanException() {
		
		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('working_plan_exceptions')
				->from('employee_settings')
				->where('`id_employee` = ' . $this->id)
		);
	}

    
    public static function getEmployees($activeOnly = true) {

        $sql = new DbQuery();
        $sql->select('`id_user`, `firstname`, `lastname`');
        $sql->from('user');
        $sql->where('`is_admin` = 1');
        if ($activeOnly) {
            $sql->where('`active` = 1');
        }

        $sql->orderBy('`lastname` ASC');

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);
    }

    
    public static function employeeExists($email) {

        if (!Validate::isEmail($email)) {
            die(Tools::displayError());
        }

        return (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_user`')
                ->from('user')
                ->where('`is_admin` = 1')
                ->where('`email` = \'' . pSQL($email) . '\'')
        );
    }

   
    public static function getEmployeesByProfile($idProfile, $activeOnly = false) {

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('user');
        $sql->where('`is_admin` = 1');
        $sql->where('`id_profile` = ' . (int) $idProfile);

        if ($activeOnly) {
            $sql->where('`active` = 1');
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);
    }

   
    public static function setLastConnectionDate($idEmployee) {

        return Db::getInstance()->update(
            'user',
            [
                'last_connection_date' => ['type' => 'sql', 'value' => 'CURRENT_DATE()'],
            ],
            '`id_user` = ' . (int) $idEmployee . ' AND `last_connection_date` < CURRENT_DATE()'
        );
    }

   
    public function getFields() {

        if (empty($this->stats_date_from) || $this->stats_date_from == '0000-00-00') {
            $this->stats_date_from = date('Y-m-d', strtotime('-1 month'));
        }

        if (empty($this->stats_compare_from) || $this->stats_compare_from == '0000-00-00') {
            $this->stats_compare_from = null;
        }

        if (empty($this->stats_date_to) || $this->stats_date_to == '0000-00-00') {
            $this->stats_date_to = date('Y-m-d');
        }

        if (empty($this->stats_compare_to) || $this->stats_compare_to == '0000-00-00') {
            $this->stats_compare_to = null;
        }

        return parent::getFields();
    }

    public function getByEmail($email, $plainTextPassword = null, $activeOnly = true) {

        if (!Validate::isEmail($email) || ($plainTextPassword && !Validate::isPasswdAdmin($plainTextPassword))) {
            return false;
        }

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('user');
        $sql->where('`is_admin` = 1');
        $sql->where('`email` = \'' . pSQL($email) . '\'');

        if ($activeOnly) {
            $sql->where('`active` = 1');
        }

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
                    'user',
                    [
                        'passwd' => pSQL($newHash),
                    ],
                    'id_user = ' . (int) $result['id_user']
                );
                $result['passwd'] = $newHash;
            } else {
                return false;
            }

        }

        $this->id = $result['id_user'];
        $this->id_profile = $result['id_profile'];

        foreach ($result as $key => $value) {

            if (property_exists($this, $key)) {
                $this->{$key}
                = $value;
            }

        }

        return $this;
    }

    
    public function isLastAdmin() {

        return ($this->isSuperAdmin()
            && Employee::countProfile($this->id_profile, true) == 1
            && $this->active
        );
    }

    
    public function isSuperAdmin() {

        return $this->id_profile == _EPH_ADMIN_PROFILE_;
    }

   
    public static function countProfile($idProfile, $activeOnly = false) {

        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from('user');
        $sql->where('`id_profile` = ' . (int) $idProfile);

        if ($activeOnly) {
            $sql->where('`active` = 1');
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($sql);
    }

   
    public function isLoggedBack() {

        if (!CacheApi::isStored('isLoggedBack' . $this->id)) {
            $result = (
                $this->id && Validate::isUnsignedId($this->id) && Employee::checkPassword($this->id, Context::getContext()->cookie->passwd)
                && (!isset(Context::getContext()->cookie->remote_addr) || Context::getContext()->cookie->remote_addr == ip2long(Tools::getRemoteAddr()) || !Configuration::get('EPH_COOKIE_CHECKIP'))
            );
            CacheApi::store('isLoggedBack' . $this->id, $result);

            return $result;
        }

        return CacheApi::retrieve('isLoggedBack' . $this->id);
    }

   
    public static function checkPassword($idEmployee, $hashedPassword) {

        if (!Validate::isUnsignedId($idEmployee) || !Validate::isPasswd($hashedPassword, 8)) {
           die(Tools::displayError());
        }

        $sql = new DbQuery();
        $sql->select('`id_user`');
        $sql->from('user');
        $sql->where('`id_user` = ' . (int) $idEmployee);
        $sql->where('`active` = 1');
        $sql->where('`passwd` = \'' . pSQL($hashedPassword) . '\'');

        return (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($sql);
    }

    public function logout() {

        if (isset(Context::getContext()->cookie)) {
            Context::getContext()->cookie->logout();
            Context::getContext()->cookie->write();
        }

        $this->id = null;
    }

   
    public function favoritePluginsList() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('plugin')
                ->from('plugin_preference')
                ->where('`id_employee` = ' . (int) $this->id)
                ->where('`favorite` = 1')
                ->where('`interest` = 1 OR `interest` IS NULL')
        );
    }

  
    public function hasAuthOnShop($idCompany) {

        return $this->isSuperAdmin();
    }

    
    public function hasAuthOnShopGroup($idCompanyGroup) {

        if ($this->isSuperAdmin()) {
            return true;
        }
        
        return false;
    }

    
    public function getImage() {

        return $this->avatar;
    }

   
    public function getLastElementsForNotify($element) {

        $element = bqSQL($element);
        $max = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MAX(`id_' . bqSQL($element) . '`) as `id_' . bqSQL($element) . '`')
                ->from(bqSQL($element) . ($element == 'order' ? 's' : ''))
        );

        // if no rows in table, set max to 0

        if ((int) $max < 1) {
            $max = 0;
        }

        return (int) $max;
    }
    
    
    
    public static function getEmployeeName($idEmployee) {
       
        $employee = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
			(new DbQuery())
				->select('firstname, lastname')
				->from('user')
				->where('`id_user` = ' . $idEmployee)
		);
        
		return $employee['firstname'].' '.$employee['lastname'];
    }
    
    public function hasAccess($tabId, $permission) {
        if (! Profile::isValidPermission($permission)) {
            throw new PrestaShopException("Invalid permission type");
        }
        $tabId = (int)$tabId;
        $tabAccess = Profile::getProfileAccess($this->id_profile, $tabId);
        return (bool)$tabAccess[$permission];
    }

}
