<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\DbException;

use queasy\helper\Arrays;

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
    public function run(array $params = array(), array $optionsOrUses = array(), $options = array())
    {
        $configInterface = 'queasy\config\ConfigInterface';
        $config = ($this->config instanceof $configInterface)
            ? $this->config->toArray()
            : $this->config;

        if (3 === func_num_args()) {
            $uses = $optionsOrUses;
        }

        if (2 === func_num_args()) {
            if (isset($config['uses'])) {
                $uses = $optionsOrUses;
            }
        }

        $options = array_merge($options, isset($config['options'])? $config['options']: array());

        if (isset($config['uses'])) {
            $sql = $this->sql();
            foreach ($config['uses'] as $use => $useSql) {
                if (isset($uses[$use])) { // Substitute placeholder with SQL and append params
                    $sql = preg_replace('/:' . $use . '/', $useSql, $sql);
                    $params = array_merge($params, $uses[$use]);
                } else { // Remove placeholder
                    $sql = preg_replace('/:' . $use . '/', '', $sql);
                }
            }

            $this->setSql($sql);
        }

        $statement = parent::run($params, $options);

        if (!isset($this->config['returns']) || ($this->config['returns'] === Db::RETURN_STATEMENT)) {
            return $statement;
        }

        $fetchMode = isset($this->config['fetchMode'])
            ? $this->config['fetchMode']
            : Db::FETCH_BOTH; // TODO: Check this, issue #73

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

            case Db::RETURN_MAP:
                $keyColumn = $this->config['keyColumn'];

                return Arrays::column($statement->fetchAll(), null, $keyColumn);

            default:
                throw new DbException('Unknown return type: ' . $this->config['returns']);
        }
    }
}

