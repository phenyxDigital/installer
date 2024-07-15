<?php

/**
 * Class InstallSqlLoader
 *
 * @since 1.0.0
 */
class InstallSqlLoader {

    /**
     * @var Db
     */
    protected $db;

    /**
     * @var array List of keywords which will be replaced in queries
     */
    protected $metadata = [];

    /**
     * @var array List of errors during last parsing
     */
    protected $errors = [];

    /**
     * InstallSqlLoader constructor.
     *
     * @param Db|null $db
     *
     * @since 1.0.0
     */
    public function __construct(Db $db = null) {

        if (is_null($db)) {
            $db = Db::getInstance();
        }

        $this->db = $db;
    }

    /**
     * Set a list of keywords which will be replaced in queries
     *
     * @param array $data
     *
     * @since 1.0.0
     */
    public function setMetaData(array $data) {

        foreach ($data as $k => $v) {
            $this->metadata[$k] = $v;
        }

    }

    /**
     * Parse a SQL file and immediately executes the query
     *
     * @param string $filename
     * @param bool $stopWhenFail
     *
     * @return bool
     * @throws PhenyxInstallerException
     * @throws PhenyxException
     *
     * @since 1.0.0
     */
    public function parseFile($filename, $stopWhenFail = true) {

        if (!file_exists($filename)) {
            throw new PhenyxInstallerException("File $filename not found");
        }

        return $this->parse(file_get_contents($filename), $stopWhenFail);
    }

    /**
     * Parse and execute a list of SQL queries
     *
     * @param string $content
     * @param bool $stopWhenFail
     *
     * @return bool
     * @throws PhenyxException
     */
    public function parse($content, $stopWhenFail = true) {

        $this->errors = [];

        $content = str_replace(array_keys($this->metadata), array_values($this->metadata), $content);
        $queries = preg_split('#;\s*[\r\n]+#', $content);

        foreach ($queries as $query) {
            $query = trim($query);

            if (!$query) {
                continue;
            }

            if (!$this->db->execute($query)) {
                $this->errors[] = [
                    'errno' => $this->db->getNumberError(),
                    'error' => $this->db->getMsgError(),
                    'query' => $query,
                ];

                if ($stopWhenFail) {
                    return false;
                }

            }

        }

        return count($this->errors) ? false : true;
    }

    /**
     * Get list of errors from last parsing
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getErrors() {

        return $this->errors;
    }

}
