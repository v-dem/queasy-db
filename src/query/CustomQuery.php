<?php

namespace queasy\db\query;

use queasy\config\ConfigInterface;

use queasy\db\Db;
use queasy\db\DbException;

class CustomQuery extends Query
{
    private $config;

    public function __construct(Db $pdo, ConfigInterface $config)
    {
        $this->config = $config;

        parent::__construct($pdo, $config->query);
    }

    /**
     * Executes SQL query and returns all selected rows.
     *
     * @param array $params Query parameters
     *
     * @return array Returned data depends on query, usually it is an array (or affected rows count for queries like INSERT, DELETE or UPDATE)
     *
     * @throws DbException On error
     */
    public function run(array $params = array())
    {
        parent::run($params);

        $returns = $this->config()->returns;

        if ($returns) {
            $fetchMode = $this->config()->fetchMode;
            $fetchArg = $this->config()->fetchArg;
            switch ($returns) {
                case Db::RETURN_ONE:
                    if (Db::FETCH_CLASS === $fetchMode) {
                        return $this->statement()->fetchObject($fetchArg? $fetchArg: 'stdClass');
                    } else {
                        return $this->statement()->fetch($fetchMode);
                    }

                    break;

                case Db::RETURN_ALL:

                default:
                    $fetchMethod = 'fetchAll';

                    if (Db::FETCH_CLASS === $fetchMode) {
                        return $this->statement()->fetchAll($fetchMode, $fetchArg);
                    } else {
                        return $this->statement()->fetchAll($fetchMode);
                    }
            }
        } else {
            return $this->statement();
        }
    }

    protected function config()
    {
        return $this->config;
    }
}

