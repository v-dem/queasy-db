<?php

namespace queasy\db;

use queasy\config\ConfigSection;
use queasy\config\ConfigTrait;

class Db
{

    use ConfigTrait;

    private static $instances = array();

    public static function getInstance($name = 'default')
    {
        if (!isset(self::$instances[$name])) {
            self::$instances = new self($name);
        }

        return self::$instances[$name];
    }

    private $pdo;
    private $tables = array();

    private function __construct($name = 'default')
    {
        try {
            $configs = $this->config();
            $config = new ConfigSection($configs->getMandatory($name));

            $this->pdo = new \PDO(
                sprintf('%s:host=%s;dbname=%s',
                    $config->getMandatory('driver'),
                    $config->getMandatory('host'),
                    $config->getMandatory('name')
                ),
                $config->getMandatory('user'),
                $config->getMandatory('password'),
                array(
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8; SET CHARACTER SET utf8;'
                )
            );
        } catch (\Exception $ex) {
            throw new DbException('Cannot connect to database.');
        }
    }

    public function __get($tableName)
    {
        if (!isset($this->tables[$tableName])) {
            $this->tables[$tableName] = new Table($this->pdo, $tableName);
        }

        return $this->tables[$tableName];
    }

    public function execute($query, array $params = array(), $isNormalized = false)
    {
        $normParams = $isNormalized? $params: $this->normalizeParams($params);

        $command = $this->pdo->prepare($query);

        $command->closeCursor();

        if (!$command->execute($normParams)) {
            throw new DbException('Can\'t execute query:');
        }
    }

    public function select($query, array $params = array(), $fetchType = \PDO::FETCH_ASSOC)
    {
        $normParams = $this->normalizeParams($params);

        $command = $this->pdo->prepare($query);

        $command->closeCursor();

        foreach ($normParams as $paramName => $paramValue) {
            if (is_array($paramValue)
                    && isset($paramValue['type'])
                    && isset($paramValue['value'])) {
                $command->bindValue($paramName, $paramValue['value'], $paramValue['type']);
            } else {
                $command->bindValue($paramName, $paramValue);
            }
        }

        if (!$command->execute()) {
            throw new DbException('Can\'t execute query.');
        }

        return $command->fetchAll($fetchType);
    }

    public function selectOne($query, array $params = array(), $fetchType = \PDO::FETCH_ASSOC)
    {
        $rows = $this->select($query, $params, $fetchType);

        return array_shift($rows);
    }

    public function selectValues($query, array $params = array(), $fetchType = \PDO::FETCH_ASSOC)
    {
        $row = $this->selectOne($query, $params, $fetchType);
        if (empty($row)) {
            throw new DbException('No values selected.');
        } else {
            return $row;
        }
    }

    public function selectValue($query, array $params = array())
    {
        $values = $this->selectValues($query, $params, \PDO::FETCH_NUM);

        return $values[0];
    }

    public function normalizeParams(array $params = array())
    {
        $normParams = array();
        foreach ($params as $paramKey => $paramValue) {
            $normParams[(strlen($paramKey) && (':' === $paramKey{0}))? $paramKey: ':' . $paramKey] = $paramValue;
        }

        return $normParams;
    }

    public function pdo()
    {
        return $this->pdo;
    }

}

