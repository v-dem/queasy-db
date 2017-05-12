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
            self::$instances[$name] = new self($name);
        }

        return self::$instances[$name];
    }

    public static function map($field, $rows)
    {
        $result = array();
        foreach ($rows as $row) {
            $result[$row[$field]] = $row;
        }

        return $result;
    }

    private $name;
    private $config;
    private $pdo;
    private $tables = array();

    private function __construct($name = 'default')
    {
        $this->name = $name;
        $this->config = $this->config()->need($name);

        try {
            $this->pdo = new \PDO(
                sprintf('%s:host=%s;dbname=%s',
                    $this->config->need('driver'),
                    $this->config->need('host'),
                    $this->config->need('name')
                ),
                $this->config->need('user'),
                $this->config->need('password'),
                $this->config->get('options')->toArray()
            );
        } catch (\Exception $ex) {
            throw new DbException('Cannot connect to database.');
        }
    }

    public function __get($tableName)
    {
        if (!isset($this->tables[$tableName])) {
            $config = $this->config->get('queries', array())[$tableName];

            $this->tables[$tableName] = new Table($this, $tableName, $config);
        }

        return $this->tables[$tableName];
    }

    public function executeNew()
    {
        
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

    public function select($query, array $params = array())
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

        return $command->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function selectOne($query, array $params = array())
    {
        $rows = $this->select($query, $params, $fetchType);

        return array_shift($rows);
    }

    public function selectValues($query, array $params = array())
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
        $values = $this->selectValues($query, $params);

        return array_shift($values);
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

    public function trans(callable $func)
    {
        $this->pdo->beginTransaction();

        try {
            $func();

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }

}

