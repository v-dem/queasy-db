<?php

namespace queasy\db;

use Exception;
use InvalidArgumentException;

use ArrayAccess;

use PDO;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use queasy\db\query\Query;
use queasy\db\query\CustomQuery;

class Db extends PDO implements ArrayAccess, LoggerAwareInterface
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

    /**
     * Create Db instance
     *
     * @param string|array|ArrayAccess $configOrDsn DSN string or config array
     * @param string $user Database user name
     * @param string $password Database user password
     * @param array $options Key-value array of driver-specific options
     * 
     */
    public function __construct($configOrDsn = null, $user = null, $password = null, array $options = null)
    {
        $config = array();
        if (null === $configOrDsn) {
            $connectionConfig = null;
        } elseif (is_string($configOrDsn)) {
            $connectionConfig = array(
                'dsn' => $configOrDsn,
                'user' => $user,
                'password' => $password,
                'options' => $options
            );
        } elseif (is_array($configOrDsn) || ($configOrDsn instanceof ArrayAccess)) {
            $config = $configOrDsn;
            $connectionConfig = isset($config['connection'])? $config['connection']: null;
        } else {
            throw DbException::invalidConstructorArguments();
        }

        $this->config = $config;

        try {
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

        if (isset($config['fetchMode'])) {
            if (!$this->setAttribute(self::ATTR_DEFAULT_FETCH_MODE, $config['fetchMode'])) {
                throw DbException::fetchModeNotSet();
            }
        }
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

    public function __invoke($sql, array $params = array())
    {
        return $this->run($sql, $params);
    }

    public function offsetGet($name)
    {
        return $this->table($name);
    }

    public function offsetSet($offset, $value)
    {
        throw DbException::notImplementedException(__CLASS__, __METHOD__);
    }

    public function offsetExists($offset)
    {
        throw DbException::notImplementedException(__CLASS__, __METHOD__);
    }

    public function offsetUnset($offset)
    {
        throw DbException::notImplementedException(__CLASS__, __METHOD__);
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
            $tablesConfig = isset($config['tables'])
                ? $config['tables']
                : array();

            $config = isset($tablesConfig[$name])
                ? $tablesConfig[$name]
                : array();

            $this->tables[$name] = new Table($this, $name, $config);
            $this->tables[$name]->setLogger($this->logger());
        }

        return $this->tables[$name];
    }

    protected function customQuery($name, array $args = array())
    {
        $queries = $this->queries();
        if (!isset($queries[$name])) {
            throw DbException::queryNotDeclared($name);
        }

        $query = new CustomQuery($this, $queries[$name]);
        $query->setLogger($this->logger());

        return $query->run($args);
    }

    public function run($sql, array $params = array())
    {
        $query = new Query($this, $sql);
        $query->setLogger($this->logger());

        return $query->run($params);
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

        $this->statements[$query] = parent::prepare($query, (null === $options)? array(): $options);

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
        if (null === $this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }
}

