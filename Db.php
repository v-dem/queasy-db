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
        $this->config = self::config()->need($name);

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

    public function __get($name)
    {
        if (!isset($this->tables[$name])) {
            $queriesConfig = $this->config->get('queries', array());
            $config = $queriesConfig[$name];

            $this->tables[$name] = new Table($this, $name, $config);
        }

        return $this->tables[$name];
    }

    public function execute($query)
    {
        $params = array();
        if (func_num_args() > 1) {
            if (is_array(func_get_arg(1))) { // Check if params passed as an array (key-value pairs)
                $params = func_get_arg(1);
                \queasy\log\Logger::info(print_r($params, true));
            } else { // Params passed as a list of variables
                $params = func_get_args();
                \queasy\log\Logger::info(print_r($params, true));

                array_shift($params); // Remove first item ($query)
            }
        }

        $command = $this->pdo->prepare($query);
        $command->closeCursor();

        $counter = 1;
        foreach ($params as $paramKey => $paramValue) {
            if (is_int($paramKey)) { // Use counter as a key if keys are numeric
                $command->bindValue(
                    $counter,
                    $paramValue,
                    is_int($paramValue)? \PDO::PARAM_INT: \PDO::PARAM_STR
                );

                $counter++;
            } else { // Use keys and prepend them with ":" when needed
                $command->bindValue(
                    (strlen($paramKey) && (':' === $paramKey{0}))? $paramKey: ':' . $paramKey,
                    $paramValue,
                    is_int($paramValue)? \PDO::PARAM_INT: \PDO::PARAM_STR
                );
            }
        }

        if (!$command->execute($params)) {
            list($sqlErrorCode, $driverErrorCode, $errorMessage) = $command->errorInfo();

            throw new DbException(sprintf('Can\'t execute query (%s): %s', $errorMessage, $query));
        }

        $rows = $command->fetchAll($this->config->get('fetchMode', \PDO::FETCH_ASSOC));

        return $rows;
    }

    public function select($query)
    {
        return call_user_func_array(array($this, 'execute'), func_get_args());
    }

    public function get($query)
    {
        $rows = call_user_func_array(array($this, 'select'), func_get_args());

        return array_shift($rows);
    }

    public function values($query)
    {
        $row = call_user_func_array(array($this, 'get'), func_get_args());
        if (empty($row)) {
            throw new DbException(sprintf('No values selected by query %s', $query));
        } else {
            return $row;
        }
    }

    public function value($query)
    {
        $values = call_user_func_array(array($this, 'values'), func_get_args());

        return array_shift($values);
    }

    public function id()
    {
        return $this->pdo->lastInsertId();
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

