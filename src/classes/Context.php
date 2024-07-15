<?php
#[AllowDynamicProperties]
/**
 * Class ContextCore
 *
 * @since 2.1.0.0
 */
class Context {

	/* @var Context */
	protected static $instance;
	
    public $company;
    
    public $license;
    
    public $step;
    
    public $smarty;
    
    public $cookie;
	
	/** @var Link */
	public $link;
	/** @var Country */
	public $country;
	/** @var Employee */
	public $language;
	/** @var AdminTab */
	

	public static function getContext() {
       
		if (!isset(static::$instance)) {
			static::$instance = new Context();
		}
		return static::$instance;
	}

	
}
