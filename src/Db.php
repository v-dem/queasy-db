<?php

namespace queasy\db;

use Exception;
use BadMethodCallException;
use InvalidArgumentException;

use PDO;

use ArrayAccess;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use queasy\db\query\Query;
use queasy\db\query\CustomQuery;

class Db extends PDO implements LoggerAwareInterface
{
    public static function expr($expr, array $bindings = array())
    {
        return new Expression($expr, $bindings);
    }

    public static function inExpr($column, array $values)
    {
        return new InExpression($column, $values);
    }

    const RETURN_STATEMENT = 1;
    const RETURN_ONE = 2;
    const RETURN_ALL = 3;
    const RETURN_VALUE = 4;

    const ATTR_USE_RETURNING = 'useReturning';

    private $tables = array();

    private $tableConfigs = array();

    private $queries = array();

    private $queryConfigs = array();

    private $useReturning = false;

    /**
     * @var LoggerInterface Logger instance
     */
    protected $logger;

    protected $lastStatement;

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

        if (!isset($options[self::ATTR_ERRMODE])) {
            $options[self::ATTR_ERRMODE] = self::ERRMODE_EXCEPTION;
        }

        $this->useReturning = isset($options[self::ATTR_USE_RETURNING]) && $options[self::ATTR_USE_RETURNING];

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

            $connection = $configOrDsn['connection'];

            $dsn = 'sqlite::memory:';
            if (isset($connection['dsn'])) {
                $dsn = $connection['dsn'];
            }

            $options = array();
            if (isset($connection['options'])) {
                $options = $connection['options'];
                if (!isset($options[self::ATTR_ERRMODE])) {
                    if (is_object($options) && method_exists($options, 'toArray')) {
                        $options = $options->toArray();
                    }

                    $options[self::ATTR_ERRMODE] = self::ERRMODE_EXCEPTION;
                }

                $this->useReturning = isset($options[self::ATTR_USE_RETURNING]) && $options[self::ATTR_USE_RETURNING];
            }

            parent::__construct(
                $dsn,
                isset($connection['user'])? $connection['user']: null,
                isset($connection['password'])? $connection['password']: null,
                $options
            );

            // $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('queasy\\db\\Statement', array($this)));

            return;
        }

        throw new InvalidArgumentException('Invalid arguments passed to Db::__construct(): $configOrDsn must be null, or a string, or an array or ArrayAccess instance');
    }

    #[\ReturnTypeWillChange]
    public function prepare($sql, array $options = array())
    {
        $this->logger->debug('Db::prepare(): SQL: ' . $sql);

        $statement = parent::prepare($sql, $options);

        if (class_exists('\\WeakReference')) { // Trick to make PHPUnit tests run. It doesn't allow to keep reference to PDOStatement otherwise.
            $this->lastStatement = \WeakReference::create($statement);
        } else {
            $this->lastStatement = $statement;
        }

        return $statement;
    }

    public function getLastStatement()
    {
        return $this->lastStatement;
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
                throw new BadMethodCallException("No method \"$name\" found.");
            }

            $this->queries[$name] = new CustomQuery($this, $this->queryConfigs[$name]);
        }

        $query = $this->queries[$name];

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

    public function useReturning()
    {
        return $this->useReturning;
    }
}

