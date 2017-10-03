<?php

namespace BIMu;

class BIMu {

    /** @var string EMu server IP */
    private $ip; 

    /** @var int EMu server port */
    private $port;

    /** @var IMuSession session variable */
    private $session;

    /** @var IMuModule module variable */
    private $module;

    /** @var string name of the module we're querying */
    private $moduleName;

    /** @var IMuTerms IMu search terms */
    private $terms;

    /** @var array The fields we'd like to return in the results */
    private $fields;

    /** @var int The exact count of records in the results */
    private $count;

    /** @var int The number of hits found from our search */
    private $hits;

    /** @var IMuModuleFetchResult The result from the search */
    private $result;

    /** @var array Any array of records returned from the search */
    private $records;

    /**
     * Object constructor
     */
    public function __construct($ip, $port, $moduleName, $login = null)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->moduleName = $moduleName;
        
        try {
            $this->session = new \IMuSession($this->ip, $this->port);
            $this->module = new \IMuModule($this->moduleName, $this->session);
        } catch (\IMuException $e) {
            print "Error initializing IMuSession and IMuModule: $e" . PHP_EOL;
        }

        if (isset($login) && is_array($login)) {
            try {
                $username = (string) key($login);
                $password = (string) reset($login);
                $this->session->login($username, $password);
            } catch (\IMuException $e) {
                print "Error logging in: $e" . PHP_EOL;
            }
        }
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
        try {
            $this->session->logout();
        } catch (\IMuException $e) {
            print "Error logging out: $e" . PHP_EOL;
        }
    }

    /**
     * Search the EMu module.
     *
     * @param array $criteria
     *   Array of key, value pairs of the fieldname and value we're looking for.
     *   This defaults to IMuTerms AND condition. Add true as third param for
     *   OR searches.
     *
     * @param array $fields
     *   The machine names of the field we want to retrieve from the Module.
     *
     * @param string $or
     *   Indicates if this is an OR search, instead of an AND.
     *
     * @return BIMu
     *   Returns this object.
     */
    public function search(array $criteria, array $fields, string $or = null): BIMu
    {
        try {
            $this->fields = $fields;

            if ($or == 'OR') {
                $this->terms = new \IMuTerms('OR');
            } else {
                $this->terms = new \IMuTerms();
            }

            foreach ($criteria as $key => $value) {
                $this->terms->add($key, $value);
            }

            $this->hits = $this->module->findTerms($this->terms);

            return $this;

        } catch (\IMuException $e) {
            print "Error adding terms and searching: $e" . PHP_EOL;
        }
    }

    /**
     * Returns the hits from a search.
     * Please note that while this is generally considered accurate, it's doesn't
     * always provide the exact number of results.
     * See the documentation:
     * http://imu.mel.kesoftware.com/doc/api/php/accessing/searching.html#number-of-matches
     *
     * @return int
     *   The number of hits.
     */
    public function hits(): int
    {
        if (empty($this->hits)) {
            return 0;
        } else {
            return $this->hits;
        }
    }

    /**
     * Returns the count from a search.
     *
     * @return int
     *   The count of records.
     */
    public function count(): int
    {
        if (empty($this->count)) {
            return 0;
        } else {
            return $this->count;
        }
    }

    /**
     * Return all results.
     *
     * @return array
     *   Returns an array of the records searched.
     */
    public function getAll(): array
    {
        try {
            $this->result = $this->module->fetch('start', 0, -1, $this->fields);
            $this->count = $this->result->count;
            $this->records = $this->result->rows;

            return $this->records;

        } catch (\IMuException $e) {
            print "Error fetching records -- getAll(): $e" . PHP_EOL;
        }
    }

    /**
     * Return an arbitrary number of records.
     *
     * @param int $number
     *   The number of results we'd like returned.
     *
     * @return array
     *   Returns an array of the records searched.
     */
    public function get(int $number): array
    {
        try {
            if ($number == 1) {
                $this->getOne();
            }
            else {
                $this->result = $this->module->fetch('start', 0, $number, $this->fields);
                $this->count = $this->result->count;
                $this->records = $this->result->rows;

                return $this->records;
            }
        } catch (\IMuException $e) {
            print "Error fetching records -- get($number): $e" . PHP_EOL;
        }
    }

    /**
     * Return the first result.
     *
     * @param int $offset
     *   The offset of the search results, from which to retrieve the record.
     *
     * @return array
     *   Returns an array of the records searched.
     */
    public function getOne(int $offset = 0): array
    {
        try {
            $this->result = $this->module->fetch('start', $offset, 1, $this->fields);
            $this->count = $this->result->count;
            $this->records = $this->result->rows;

            if (empty($this->records)) {
                return [];
            } else {
                return $this->records[0];
            }
        } catch (\IMuException $e) {
            print "Error fetching records -- getOne($offset): $e" . PHP_EOL;
        }
    }
}
