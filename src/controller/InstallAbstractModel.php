<?php

/**
 * Class InstallAbstractModel
 *
 * @since 1.0.0
 */
abstract class InstallAbstractModel {

    /**
     * @var InstallLanguages
     */
    public $language;

    /**
     * @var array List of errors
     */
    protected $errors = [];

    /**
     * InstallAbstractModel constructor.
     *
     * @since 1.0.0
     * @throws PhenyxInstallerException
     */
    public function __construct() {

        $this->language = InstallLanguages::getInstance();
    }

    /**
     * @param $errors
     *
     * @since 1.0.0
     */
    public function setError($errors) {

        if (!is_array($errors)) {
            $errors = [$errors];
        }

        $this->errors[] = $errors;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getErrors() {

        return $this->errors;
    }

}
