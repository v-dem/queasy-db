<?php

namespace queasy\db;

use PDO as BasePDO;
use Exception;
use InvalidArgumentException;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use queasy\config\ConfigInterface;
use queasy\config\Config;

use queasy\db\query\CustomQuery;

class Db
{
    use LoggerAwareTrait;

    const DEFAULT_FETCH_MODE = BasePDO::FETCH_ASSOC;

    /**
     * Creates a key/value map by an array key or object field.
     *
     * @param string $field Field or key name
     * @param array $rows Array of arrays or objects
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

    private $config;

    private $pdo;

    private $tables;
    private $queries;

    public function __construct(ConfigInterface $config, LoggerInterface $logger = null)
    {
        $this->config = $config;

        $this->setLogger($logger? $logger: new NullLogger());
    }

    public function __get($name)
    {
        return $this->table($name);
    }

    public function __call($name, array $args = array())
    {
        return call_user_func_array(array($this, 'query'), array_merge(array($name), $args));
    }

    public function table($name)
    {
        if (isset($this->tables()->$name)) {
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

    public function query($name)
    {
        if (isset($this->queries()->$name)) {
            $args = func_get_args();

            array_shift($args);

            $query = new CustomQuery($this->queries()->$name, $this->pdo());

            return call_user_func_array(array($query, 'run'), $args);
        } else {
            throw new DbException(sprintf('Query "%s" was not declared in configuration.', $name));
        }
    }

    public function run($queryClass)
    {
        $interfaces = class_implements($queryClass);
        if (!$interfaces
                || !isset($interfaces['queasy\db\query\QueryInterface'])) {
            throw new InvalidArgumentException(sprintf('Query class "%s" does not implement queasy\db\query\QueryInterface.', $queryClass));
        } else {
            $args = func_get_args();

            array_shift($args); // Remove $queryClass

            $queryString = array_shift($args);
            if (!$queryString || !is_string($queryString)) {
                throw new InvalidArgumentException('Query string is missing or not a string.');
            }

            $query = new $queryClass($this->pdo(), $queryString);

            return call_user_func_array(array($query, 'run'), $args);
        }
    }

    public function id($sequence = null)
    {
        return $this->pdo()->lastInsertId($sequence);
    }

    public function trans($func)
    {
        if (!is_callable($func)) {
            throw new InvalidArgumentException(); // TODO: Add error message
        }

        $this->pdo()->beginTransaction();

        try {
            $func();

            $this->pdo()->commit();
        } catch (Exception $ex) {
            $this->pdo()->rollBack();

            throw $ex;
        }
    }

    protected function pdo()
    {
        if (!$this->pdo) {
            try {
                $connection = $this->config()->connection;

                $this->pdo = new PDO(
                    sprintf('%s:host=%s;dbname=%s',
                        $connection->driver,
                        $connection->host,
                        $connection->get('port'),
                        $connection->name
                    ),
                    $connection->get('user'),
                    $connection->get('password'),
                    $connection->get('options')
                );

                $fetchMode = $this->config()->get('fetchMode', static::DEFAULT_FETCH_MODE);
                if (!$this->pdo->setAttribute(BasePDO::ATTR_DEFAULT_FETCH_MODE, $fetchMode)) {
                    $this->logger->warning('Cannot set default fetch mode.');
                }
            } catch (Exception $ex) {
                throw new DbException('Cannot connect to database.', 0, $ex);
            }
        }

        return $this->pdo;
    }

    protected function config()
    {
        return $this->config;
    }

    protected function tables()
    {
        if (!$this->tables) {
            $this->tables = $this->config()->tables;
            if (!$this->tables) {
                $this->tables = new Config(array());
            }
        }

        return $this->tables;
    }

    protected function queries()
    {
        if (!$this->queries) {
            $this->queries = $this->config()->queries;
            if (!$this->queries) {
                $this->queries = new Config(array());
            }
        }

        return $this->queries;
    }

    protected function logger()
    {
        return $this->logger;
    }
}

