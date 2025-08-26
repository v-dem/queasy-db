<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\DbException;

class CustomQuery extends Query
{
    private $config;

    public function __construct(Db $db, $config)
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
        $configInterface = 'queasy\config\ConfigInterface';
        $config = ($this->config instanceof $configInterface)
            ? $this->config->toArray()
            : $this->config;

        $options = array_merge($options, isset($config['options'])? $config['options']: array());

        $statement = parent::run($params, $options);

        if (!isset($this->config['returns']) || ($this->config['returns'] === Db::RETURN_STATEMENT)) {
            return $statement;
        }

        $fetchMode = isset($this->config['fetchMode'])
            ? $this->config['fetchMode']
            : Db::FETCH_BOTH;

        $fetchClass = isset($this->config['fetchClass'])
            ? $this->config['fetchClass']
            : 'stdClass';

        switch ($this->config['returns']) {
            case Db::RETURN_ONE:
                return (Db::FETCH_CLASS === $fetchMode)
                    ? $statement->fetchObject($fetchClass)
                    : $statement->fetch($fetchMode);

            case Db::RETURN_ALL:
                return (Db::FETCH_CLASS === $fetchMode)
                    ? $statement->fetchAll($fetchMode, $fetchClass)
                    : $statement->fetchAll($fetchMode);

            case Db::RETURN_VALUE:
                return $statement->fetchColumn();

            default:
                throw new DbException('Unknown return type: ' . $this->config['returns']);
        }
    }
}

