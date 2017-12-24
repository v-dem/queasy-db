<?php

namespace queasy\db\query;

use PDO;

use queasy\config\ConfigInterface;

class Custom extends Query
{
    private $fetchMode;

    private $fetchClass;

    public function __construct(ConfigInterface $config, PDO $pdo)
    {
        $this->fetchMode = $config->get('fetchMode');
        $this->fetchClass = $config->get('fetchClass');

        parent::__construct($pdo, $config->query);
    }

    /**
     * Executes SQL query and returns all selected rows.
     *
     * @param mixed $args Query arguments, can be an array or a list of function arguments
     *
     * @return array Returned data depends on query, usually it is an array (empty array for queries like INSERT, DELETE or UPDATE)
     *
     * @throws DbException On error
     */
    public function run()
    {
        call_user_func_array('parent::run', func_get_args());

        return $this->statement()->fetchAll($this->fetchMode(), $this->fetchClass());
    }

    protected function fetchMode()
    {
        return $this->fetchMode;
    }

    protected function fetchClass()
    {
        return $this->fetchClass;
    }
}

