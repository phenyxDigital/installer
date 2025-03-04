<?php

class InstallModelDatabase extends InstallAbstractModel {

    /**
     * Check database configuration and try a connection
     *
     * @param string $server
     * @param string $database
     * @param string $login
     * @param string $password
     * @param string $prefix
     * @param bool $clear
     * @return array List of errors
     */
    public function testDatabaseSettings($server, $database, $login, $password, $prefix, $clear = false) {

        $errors = [];

        // Check if fields are correctly typed

        if (!$server || !Validate::isUrl($server)) {
            $errors[] = $this->language->l('Server name is not valid');
        }

        if (!$database) {
            $errors[] = $this->language->l('You must enter a database name');
        }

        if (!$login) {
            $errors[] = $this->language->l('You must enter a database login');
        }

        if ($prefix && !Validate::isTablePrefix($prefix)) {
            $errors[] = $this->language->l('Tables prefix is invalid');
        }

        if (!$errors) {
            $dbtype = ' (' . Db::getClass() . ')';
            // Try to connect to database

            switch (Db::checkConnection($server, $login, $password, $database, true)) {
            case 0:

                if (!Db::checkEncoding($server, $login, $password)) {
                    $errors[] = $this->language->l('Cannot convert database data to utf-8') . $dbtype;
                }

                // Check if a table with same prefix already exists

                if (!$clear && Db::hasTableWithSamePrefix($server, $login, $password, $database, $prefix)) {
                    $errors[] = $this->language->l('At least one table with same prefix was already found, please change your prefix or drop your database');
                }

                if (!Db::checkAutoIncrement($server, $login, $password)) {
                    $errors[] = $this->language->l('The values of auto_increment increment and offset must be set to 1');
                }

                if (($create_error = Db::checkCreatePrivilege($server, $login, $password, $database, $prefix)) !== true) {
                    $errors[] = $this->language->l(sprintf('Your database login does not have the privileges to create table on the database "%s". Ask your hosting provider:', $database));

                    if ($create_error != false) {
                        $errors[] = $create_error;
                    }

                }

                break;

            case 1:
                $errors[] = $this->language->l('Database Server is not found. Please verify the login, password and server fields') . $dbtype;
                break;

            case 2:
                $error = $this->language->l('Connection to MySQL server succeeded, but database "%s" not found', $database) . $dbtype;

                if ($this->createDatabase($server, $database, $login, $password, true)) {
                    $error .= '<p>' . sprintf('<input type="button" value="%s" class="button" id="btCreateDB">', $this->language->l('Attempt to create the database automatically')) . '</p>
                        <script type="text/javascript">bindCreateDB();</script>';
                }

                $errors[] = $error;
                break;
            }

        }

        return $errors;
    }

    public function createDatabase($server, $database, $login, $password, $dropit = false) {

        $class = Db::getClass();
        return call_user_func([$class, 'createDatabase'], $server, $login, $password, $database, $dropit);
    }

}
