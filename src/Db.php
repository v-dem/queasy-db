<?php

namespace queasy\db;

use Exception;

use PDO;
use PDOException;

use ArrayAccess;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use queasy\db\query\Query;
use queasy\db\query\CustomQuery;

class Db extends PDO implements ArrayAccess, LoggerAwareInterface
{
    const DEFAULT_FETCH_MODE = PDO::FETCH_ASSOC;

    const RETURN_STATEMENT = 1;
    const RETURN_ONE = 2;
    const RETURN_ALL = 3;
    const RETURN_VALUE = 4;

    private $tables = array();

    private $queries = array();

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
        $this->logger = new NullLogger();

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
        } catch (PDOException $e) {
            throw DbException::connectionFailed($e);
        }

        if (isset($config['queries'])) {
            $this->queries = $config['queries'];
        }

        if (isset($config['statement'])) {
            if (!$this->setAttribute(self::ATTR_STATEMENT_CLASS, array($config['statement'], array($this)))) {
                throw DbException::statementClassNotSet();
            }
        }

        $errorMode = isset($config['errorMode'])? $config['errorMode']: self::ERRMODE_EXCEPTION;
        if (!$this->setAttribute(self::ATTR_ERRMODE, $errorMode)) {
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
        if (!isset($this->queries[$name])) {
            throw DbException::queryNotDeclared($name);
        }

        $query = new CustomQuery($this, $this->queries[$name]);
        $query->setLogger($this->logger);

        $params = array_shift($args);
        $options = array_shift($args);

        return $query->run(
            empty($params)? array(): $params,
            empty($options)? array(): $options
        );
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

    public function table($name)
    {
        if (!isset($this->tables[$name])) {
            $tablesConfig = isset($this->config['tables'])
                ? $this->config['tables']
                : array();

            $tableConfig = isset($tablesConfig[$name])
                ? $tablesConfig[$name]
                : array();

            $this->tables[$name] = new Table($this, $name, $tableConfig);
            $this->tables[$name]->setLogger($this->logger);
        }

        return $this->tables[$name];
    }

    public function run($sql, array $params = array(), array $options = array())
    {
        $query = new Query($this, $sql);
        $query->setLogger($this->logger);

        return $query->run($params, $options);
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

    protected function logger()
    {
        return $this->logger;
    }
}

