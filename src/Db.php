<?php

namespace queasy\db;

use Exception;
use BadMethodCallException;
use InvalidArgumentException;
use PDOException;

use PDO;

use ArrayAccess;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use queasy\db\query\Query;
use queasy\db\query\CustomQuery;

class Db extends PDO implements ArrayAccess, LoggerAwareInterface
{
    const RETURN_STATEMENT = 1;
    const RETURN_ONE = 2;
    const RETURN_ALL = 3;
    const RETURN_VALUE = 4;

    private $tables = array();

    private $tableConfigs = array();

    private $queries = array();

    private $queryConfigs = array();

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
     * @throws InvalidArgumentException
     * @throws DbException
     */
    public function __construct($configOrDsn = null, $user = null, $password = null, array $options = array())
    {
        $this->logger = new NullLogger();

        if (null === $configOrDsn) {
            parent::__construct('sqlite::memory:', $user, $password, $options);

            return;
        }

        if (is_string($configOrDsn)) {
            parent::__construct($configOrDsn, $user, $password, $options);

            return;
        }

        if (is_array($configOrDsn) || ($configOrDsn instanceof ArrayAccess)) {
            if (isset($configOrDsn['tables'])) {
                $this->tableConfigs = $configOrDsn['tables'];
            }

            if (isset($configOrDsn['queries'])) {
                $this->queryConfigs = $configOrDsn['queries'];
            }

            $connection = $configOrDsn;
            if (isset($configOrDsn['connection'])) {
                $connection = $configOrDsn['connection'];
            }

            if (!isset($connection['dsn'])) {
                throw new InvalidArgumentException('Missing "dsn" key');
            }

            $options = array();
            if (isset($connection['options'])) {
                $options = $connection['options'];
                if (!isset($options[self::ATTR_ERRMODE])) {
                    $options[self::ATTR_ERRMODE] = self::ERRMODE_EXCEPTION;
                }
            }

            parent::__construct(
                $connection['dsn'],
                isset($connection['user'])? $connection['user']: null,
                isset($connection['password'])? $connection['password']: null,
                $options
            );

            return;
        }

        throw new InvalidArgumentException('Invalid arguments passed to Db::__construct(): $configOrDsn must be null, or a string, or an array or ArrayAccess instance');
    }

    /**
     * Set a logger.
     *
     * @param LoggerInterface $logger Logger instance
     */
    #[\ReturnTypeWillChange]
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
            if (!isset($this->queryConfigs[$name])) {
                throw new BadMethodCallException(sprintf('No method "%s" found.', $name));
            }

            $this->queries[$name] = new CustomQuery($this, $this->queryConfigs[$name]);
        }

        $query = $this->queries[$name];
        $query->setLogger($this->logger);

        $params = array_shift($args);
        $options = array_shift($args);

        return $query(
            empty($params)? array(): $params,
            empty($options)? array(): $options
        );
    }

    public function __invoke($sql, array $params = array(), array $options = array())
    {
        return $this->run($sql, $params, $options);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($name)
    {
        return $this->table($name);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Get Table instance.
     *
     * @param string $name Table name
     *
     * @return Table Instance
     */
    public function table($name)
    {
        if (!isset($this->tables[$name])) {
            $config = isset($this->tableConfigs[$name])
                ? $this->tableConfigs[$name]
                : array();

            $this->tables[$name] = new Table($this, $name, $config);
            $this->tables[$name]->setLogger($this->logger);
        }

        return $this->tables[$name];
    }

    /**
     * Execute a custom query.
     *
     * @param string $sql Custom SQL query
     * @param array $params Optional
     * @param array $options Optional
     *
     * @return PDOStatement Instance
     */
    public function run($sql, array $params = array(), array $options = array())
    {
        $query = new Query($this, $sql);
        $query->setLogger($this->logger);

        return $query($params, $options);
    }

    /**
     * Short-hand alias of PDO's `lastInsertId()`
     *
     * @param string $sequence Optional. Sequence name
     *
     * @return string|false Id of last inserted record or sequence value, or false on fail
     */
    public function id($sequence = null)
    {
        return $this->lastInsertId($sequence);
    }

    /**
     * Call a function inside transaction.
     *
     * @param callable $func Function to call
     *
     * @throws Exception Any exception thrown inside $func
     */
    public function trans($func)
    {
        if (!is_callable($func)) {
            throw new InvalidArgumentException('Argument is not callable.');
        }

        $this->beginTransaction();

        try {
            $result = $func();

            if ($this->inTransaction()) {
                $this->commit();
            } else {
                $this->logger->debug('Commit check: Transaction was not started or already committed.');
            }

            return $result;
        } catch (Exception $e) {
            if ($this->inTransaction()) {
                $this->rollBack();
            } else {
                $this->logger->debug('Rollback check: Transaction was not started or already committed.');
            }

            throw $e;
        }
    }

    /**
     * Return logger instance.
     *
     * @return Psr\Log\LoggerInterface Logger instance
     */
    protected function logger()
    {
        return $this->logger;
    }
}

