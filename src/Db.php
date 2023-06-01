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
    private static function getConnectionConfig($configOrDsn = null, $user = null, $password = null, $options = null)
    {
        if (null === $configOrDsn) {
            return null;
        }

        if (is_string($configOrDsn)) {
            return array(
                'dsn' => $configOrDsn,
                'user' => $user,
                'password' => $password,
                'options' => $options
            );
        }

        if (is_array($configOrDsn) || ($configOrDsn instanceof ArrayAccess)) {
            return isset($configOrDsn['connection'])? $configOrDsn['connection']: null;
        }

        throw new InvalidArgumentException('Wrong constructor arguments.');
    }

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
     * @throws InvalidArgumentException
     * @throws DbException
     */
    public function __construct($configOrDsn = null, $user = null, $password = null, array $options = array())
    {
        $this->logger = new NullLogger();

        $config = array();
        if (is_array($configOrDsn) || ($configOrDsn instanceof ArrayAccess)) {
            $config = $configOrDsn;
        }

        $this->config = $config;

        $connectionConfig = self::getConnectionConfig($configOrDsn, $user, $password, $options);
        $connection = new Connection($connectionConfig);

        try {
            $options = isset($config['options'])? $config['options']: $options;
            if ($options instanceof \queasy\config\Config) {
                $options = $options->toArray();
            }

            parent::__construct(
                $connection(),
                isset($connectionConfig['user'])? $connectionConfig['user']: $user,
                isset($connectionConfig['password'])? $connectionConfig['password']: $password,
                $options
            );
        } catch (PDOException $e) {
            throw new DbException('Cannot initialize PDO: ' . $e->getMessage(), 0, $e);
        }

        if (isset($config['queries'])) {
            $this->queries = $config['queries'];
        }

        if (isset($config['options']) && !isset($config['options'][self::ATTR_ERRMODE])) {
            $this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
        }
    }

    /**
     * Set a logger.
     *
     * @param LoggerInterface $logger Logger instance
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
            throw new BadMethodCallException(sprintf('No method "%s" found.', $name));
        }

        $query = new CustomQuery($this, $this->queries[$name]);
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
            $result = $func($this);

            $this->commit();

            return $result;
        } catch (Exception $e) {
            $this->rollBack();

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

