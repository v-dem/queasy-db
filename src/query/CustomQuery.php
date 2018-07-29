<?php

namespace queasy\db\query;

use PDO;

use queasy\config\ConfigInterface;

use queasy\db\Db;
use queasy\db\DbException;

class CustomQuery extends Query
{
    private $config;

    public function __construct(PDO $pdo, ConfigInterface $config)
    {
        $this->config = $config;

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

        $returns = $this->config()->returns;

        $this->logger()->debug('$returns: ', array('returns' => $returns));

        if ($returns) {
            $fetchMode = $this->config()->fetchMode;
            $fetchArg = $this->config()->fetchArg;
            switch ($returns) {
                case Db::RETURN_ONE:
                    if (PDO::FETCH_CLASS === $fetchMode) {
                        return $this->statement()->fetchObject($fetchArg? $fetchArg: 'stdClass');
                    } else {
                        return $this->statement()->fetch($fetchMode);
                    }

                    break;

                case Db::RETURN_ALL:

                default:
                    $fetchMethod = 'fetchAll';

                    if (PDO::FETCH_CLASS === $fetchMode) {
                        return $this->statement()->fetchAll($fetchMode, $fetchArg);
                    } else {
                        return $this->statement()->fetchAll($fetchMode);
                    }
            }
        } else { // $returns is not set or 0 - the same as Db::RETURN_STATEMENT (default)
            return $this->statement();
        }
    }
    /*
    protected function internalFetch($fetchMethod, $fetchMode, $fetchArg = null)
    {
        return ($fetchArg)
            ? $this->statement()->$fetchMethod($fetchMode, $fetchArg)
            : $this->statement()->$fetchMethod($fetchMode);
    }
    */
    protected function config()
    {
        return $this->config;
    }
}

