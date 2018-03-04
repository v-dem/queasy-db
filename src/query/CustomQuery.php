<?php

namespace queasy\db\query;

use PDO;

use queasy\config\ConfigInterface;

class CustomQuery extends Query
{
    private $fetchMode;

    private $fetchArg;

    public function __construct(PDO $pdo, ConfigInterface $config)
    {
        $this->fetchMode = $config->get('fetchMode');
        $this->fetchArg = $config->get('fetchArg');

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
        parent::run(func_get_args());

        if ($this->fetchArg()) {
            return $this->statement()->fetchAll($this->fetchMode(), $this->fetchArg());
        } else {
            return $this->statement()->fetchAll($this->fetchMode());
        }
    }

    protected function fetchMode()
    {
        return $this->fetchMode;
    }

    protected function fetchArg()
    {
        return $this->fetchArg;
    }
}

