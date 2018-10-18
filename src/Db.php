<?php

namespace queasy\db;

use Exception;
use InvalidArgumentException;

use PDO;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use queasy\config\ConfigInterface;
use queasy\config\ConfigAwareTrait;

use queasy\db\query\CustomQuery;

class Db extends PDO
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;

    const DEFAULT_FETCH_MODE = PDO::FETCH_ASSOC;

    const RETURN_STATEMENT = 0;
    const RETURN_ONE = 1;
    const RETURN_ALL = 2;

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
                throw InvalidArgumentException::rowsUnexpectedValue();
            }
        }

        return $result;
    }

    private $tables = array();

    private $queries = array();

    private $statements = array();

    public function __construct(ConfigInterface $config, LoggerInterface $logger = null)
    {
        $this->setConfig($config);

        $this->setLogger($logger? $logger: new NullLogger());

        try {
            $connection = $config->connection;
            $connectionString = new ConnectionString($connection);
            parent::__construct(
                $connectionString->get(),
                $connection->user,
                $connection->password,
                $connection->options
            );
        } catch (Exception $e) {
            throw DbException::connectionFailed($e);
        }

        if (!$this->setAttribute(self::ATTR_STATEMENT_CLASS, array('\queasy\db\Statement', array($this)))) {
            throw DbException::statementClassNotSet();
        }

        if (!$this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION)) {
            throw DbException::errorModeNotSet();
        }

        $fetchMode = $config('fetchMode', static::DEFAULT_FETCH_MODE);
        if (!$this->setAttribute(self::ATTR_DEFAULT_FETCH_MODE, $fetchMode)) {
            throw DbException::fetchModeNotSet();
        }
    }

    public function __get($name)
    {
        return $this->table($name);
    }

    public function __call($name, array $args = array())
    {
        return $this->customQuery($name, $args);
    }

    public function prepare($query, $options = null)
    {
        $statement = $this->statement($query, $options);
        $statement->closeCursor();

        return $statement;
    }

    public function table($name)
    {
        if (!isset($this->tables[$name])) {
            $queriesConfig = isset($this->config['queries'])
                ? $this->config['queries']
                : array();

            $config = isset($queriesConfig[$name])
                ? $queriesConfig[$name]
                : array();

            $this->tables[$name] = new Table($this, $name, $config);
        }

        return $this->tables[$name];
    }

    protected function customQuery($name, array $args = array())
    {
        if (isset($this->queries()->$name)) {
            $query = new CustomQuery($this, $this->queries()->$name);
            $query->setLogger($this->logger());

            return $query->run($args);
        } else {
            throw DbException::queryNotDeclared($name);
        }
    }

    public function run($queryClass)
    {
        $interfaces = class_implements($queryClass);
        if (!$interfaces
                || !isset($interfaces['queasy\db\query\QueryInterface'])) {
            throw InvalidArgumentException::queryInterfaceNotImplemented($queryClass);
        } else {
            $args = func_get_args();

            array_shift($args); // Remove $queryClass arg

            $queryString = array_shift($args);
            if (!$queryString || !is_string($queryString)) {
                throw InvalidArgumentException::missingQueryString();
            }

            $query = new $queryClass($this, $queryString);
            $query->setLogger($this->logger());

            return $query->run($args);
        }
    }

    public function id($sequence = null)
    {
        return $this->lastInsertId($sequence);
    }

    public function trans($func)
    {
        if (!is_callable($func)) {
            throw InvalidArgumentException::argumentNotCallable();
        }

        $this->beginTransaction();

        try {
            $func($this);

            $this->commit();
        } catch (Exception $ex) {
            $this->rollBack();

            throw $ex;
        }
    }

    protected function statement($query, $options = null)
    {
        $statement = null;
        if (isset($this->statements[$query])) {
            $statement = $this->statements[$query];

            // Check if it is NOT a SELECT statement, we do not cache them
            if (0 === $statement->columnCount()) {
                return $statement;
            }
        }

        $this->statements[$query] = parent::prepare($query, is_null($options)? array(): $options);

        return $this->statements[$query];
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

    protected function config()
    {
        return $this->config;
    }

    protected function logger()
    {
        return $this->logger;
    }
}

