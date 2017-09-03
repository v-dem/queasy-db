<?php

namespace queasy\db;

use queasy\config\ConfigTrait;

class Db
{

    const DEFAULT_FETCH_MODE = \PDO::FETCH_ASSOC;

    use ConfigTrait;

    private static $instances = array();

    public static function instance($name = 'default')
    {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self($name);
        }

        return self::$instances[$name];
    }

    /**
     * Creates a key/value map by an array key or object field.
     *
     * @param string $field Field or key name
     * @param array Array of arrays or objects
     *
     * @return array Array containing $field as a key and responsive row as a value
     */
    public static function map($field, array $rows)
    {
        $result = array();
        foreach ($rows as $row) {
            if (is_object($row)) {
                $result[$row[$field]] = $row;
            } elseif (is_array($row)) {
                $result[$row->$field] = $row;
            } else {
                throw new \InvalidArgumentException();
            }
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
                $this->config->get('options', array())->toArray()
            );
        } catch (\Exception $ex) {
            throw new DbException('Cannot connect to database.');
        }
    }

    public function __get($name)
    {
        return $this->table($name);
    }

    public function __invoke($name)
    {
        return $this->table($name);
    }

    public function table($name)
    {
        if (!isset($this->tables[$name])) {
            $queriesConfig = isset($this->config['queries'])
                ? $this->config->get('queries', array())
                : array();

            $config = isset($queriesConfig[$name])
                ? $queriesConfig[$name]
                : array();

            $this->tables[$name] = new Table($this, $name, $config);
        }

        return $this->tables[$name];
    }

    /**
     * Executes SQL $query
     *
     * @param string $query Query code
     * @param mixed $args Query arguments, can be an array or a list
     *
     * @return array Returned data depends on query, usually it is an array (empty array for queries like INSERT, DELETE or UPDATE)
     *
     * @throws DbException On error
     */
    public function execute($query, $args = null)
    {
        $params = array();
        if (func_num_args() > 1) {
            if (is_array(func_get_arg(1))) { // Check if params passed as an array (key-value pairs)
                $params = func_get_arg(1);
            } else { // Params passed as a list of variables
                $params = func_get_args();

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

        $rows = $command->fetchAll($this->config->get('fetchMode', self::DEFAULT_FETCH_MODE));

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

    public function trans($func)
    {
        if (!is_callable($func)) {
            throw new InvalidArgumentException(); // TODO: Add error message
        }

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

