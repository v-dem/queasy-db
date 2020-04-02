<?php

namespace queasy\db\query;

use PDO;

use queasy\db\Db;

class CustomQuery extends Query
{
    private $config;

    public function __construct(PDO $db, $config)
    {
        $this->config = $config;

        parent::__construct($db, $config['sql']);
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
    public function run(array $params = array(), array $options = array())
    {
        $config = $this->config;
        if (is_object($config) && method_exists($config, 'toArray')) {
            $config = $config->toArray();
        }

        $options = $options + (isset($config['options'])? $config['options']: array());

        $statement = parent::run($params, $options);

        $returns = isset($this->config['returns'])? $this->config['returns']: null;

        if ($returns) {
            $fetchMode = isset($this->config['fetchMode'])? $this->config['fetchMode']: null;
            $fetchArg = isset($this->config['fetchArg'])? $this->config['fetchArg']: null;
            switch ($returns) {
                case Db::RETURN_ONE:
                    return (Db::FETCH_CLASS === $fetchMode)
                        ? $statement->fetchObject($fetchArg? $fetchArg: 'stdClass')
                        : $statement->fetch($fetchMode);

                case Db::RETURN_ALL:
                    return (Db::FETCH_CLASS === $fetchMode)
                        ? $statement->fetchAll($fetchMode, $fetchArg)
                        : $statement->fetchAll($fetchMode);

                case Db::RETURN_VALUE:
                    $row = $statement->fetch();
                    $value = array_shift($row);
                    return $value;

                default:
                    return $statement;
            }
        } else {
            return $statement;
        }
    }
}

