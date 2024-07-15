<?php

/**
 * Class InstallModelSystem
 *
 * @since 1.0.0
 */
class InstallModelSystem extends InstallAbstractModel {

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function checkRequiredTests() {

        return self::checkTests(ConfigurationTest::getDefaultTests());
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function checkOptionalTests() {

        return self::checkTests(ConfigurationTest::getDefaultTestsOp());
    }

    /**
     * @param array $list
     *
     * @return array
     *
     * @since 1.0.0 Removed $type parameter
     */
    public function checkTests($list) {

        $tests = ConfigurationTest::check($list);

        $success = true;

        foreach ($tests as $result) {
            $success &= ($result == 'ok') ? true : false;
        }

        return [
            'checks'  => $tests,
            'success' => $success,
        ];
    }

}
