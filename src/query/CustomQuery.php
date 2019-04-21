<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\DbException;

class CustomQuery extends Query
{
    private $config;

    public function __construct(Db $pdo, $config)
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

        $config = $this->config();

        $returns = isset($config['returns'])? $config['returns']: null;

        if ($returns) {
            $fetchMode = isset($config['fetchMode'])? $config['fetchMode']: null;
            $fetchArg = isset($config['fetchArg'])? $config['fetchArg']: null;
            switch ($returns) {
                case Db::RETURN_ONE:
                    return (Db::FETCH_CLASS === $fetchMode)
                        ? $this->statement()->fetchObject($fetchArg? $fetchArg: 'stdClass')
                        : $this->statement()->fetch($fetchMode);

                case Db::RETURN_ALL:

                default:
                    $fetchMethod = 'fetchAll';
                    return (Db::FETCH_CLASS === $fetchMode)
                        ? $this->statement()->fetchAll($fetchMode, $fetchArg)
                        : $this->statement()->fetchAll($fetchMode);
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

