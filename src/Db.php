<?php

namespace queasy\db;

use Exception;
use InvalidArgumentException;

use PDO;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

use queasy\db\query\CustomQuery;

class Db extends PDO
{
    const DEFAULT_FETCH_MODE = PDO::FETCH_ASSOC;

    const RETURN_STATEMENT = 0;
    const RETURN_ONE = 1;
    const RETURN_ALL = 2;

    private $tables = array();

    private $queries = array();

    private $statements = array();

    /**
     * @var array|ArrayAccess Database config
     */
    protected $config;

    /**
     * @var LoggerInterface Logger instance
     */
    protected $logger;

    public function __construct($config = array())
    {
        $this->setConfig($config);

        $config = $this->config();

        try {
            $connectionConfig = isset($config['connection'])? $config['connection']: null;
            $connectionString = new Connection($connectionConfig);
            parent::__construct(
                $connectionString(),
                isset($connectionConfig['user'])? $connectionConfig['user']: null,
                isset($connectionConfig['password'])? $connectionConfig['password']: null,
                isset($connectionConfig['options'])? $connectionConfig['options']: null
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
    }

    /**
     * Sets a config.
     *
     * @param array|ArrayAccess $config
     */
    public function setConfig($config)
    {
        $this->config = is_null($config)
            ? array()
            : $config;
    }

    /**
     * Sets a logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

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
            $config = $this->config();
            $queriesConfig = isset($config['queries'])
                ? $config['queries']
                : array();

            $config = isset($queriesConfig[$name])
                ? $queriesConfig[$name]
                : array();

            $this->tables[$name] = new Table($this, $name, $config);
            $this->tables[$name]->setLogger($this->logger());
        }

        return $this->tables[$name];
    }

    protected function customQuery($name, array $args = array())
    {
        $queries = $this->queries();
        if (isset($queries[$name])) {
            $query = new CustomQuery($this, $queries[$name]);
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
        } catch (Exception $e) {
            $this->rollBack();

            throw $e;
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
            $config = $this->config();
            $this->tables = $config['tables'];
            if (!$this->tables) {
                $this->tables = array();
            }
        }

        return $this->tables;
    }

    protected function queries()
    {
        if (!$this->queries) {
            $config = $this->config();
            $this->queries = $config['queries'];
            if (!$this->queries) {
                $this->queries = array();
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
        if (is_null($this->logger)) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }
}

