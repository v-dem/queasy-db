<?php

namespace queasy\db;

use PDO;
use ArrayAccess;
use Countable;
use Iterator;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use queasy\db\query\CustomQuery;
use queasy\db\query\CountQuery;
use queasy\db\query\SingleInsertQuery;
use queasy\db\query\SingleNamedInsertQuery;
use queasy\db\query\BatchInsertQuery;
use queasy\db\query\BatchNamedInsertQuery;
use queasy\db\query\BatchSeparatelyNamedInsertQuery;
use queasy\db\query\UpdateQuery;
use queasy\db\query\SelectQuery;

class Table implements ArrayAccess, Countable, Iterator, LoggerAwareInterface
{
    private $db;

    private $name;

    private $fields;

    private $rows;

    /**
     * Config instance.
     *
     * @var array|ArrayAccess
     */
    protected $config;

    /**
     * Logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(PDO $db, $name, $config = array())
    {
        $this->logger = new NullLogger();

        $this->db = $db;
        $this->name = $name;
        $this->fields = array();
        $this->rows = null;
        $this->setConfig($config);
    }

    public function __get($fieldName)
    {
        return $this[$fieldName];
    }

    public function count()
    {
        $query = new CountQuery($this->db(), $this->name());
        $query->setLogger($this->logger());

        $statement = $query->run();

        $row = $statement->fetch();

        return array_shift($row);
    }

    public function current()
    {
        return current($this->rows);
    }

    public function key()
    {
        return key($this->rows);
    }

    public function next()
    {
        return next($this->rows);
    }

    public function rewind()
    {
        $query = new SelectQuery($this->db(), $this->name());

        $statement = $query->run();

        $this->rows = $statement->fetchAll();
    }

    public function valid()
    {
        return isset($this->rows[$this->key()]);
    }

    public function insert()
    {
        $this->logger()->debug('INSERT params: ' . print_r(func_get_args(), true));

        $isSingleInsert = true;
        $params = (1 === func_num_args())? func_get_arg(0): func_get_args();
        if (null === $params) {
            throw new DbException('Cannot assign null to table field.');
        } elseif (is_array($params)) {
            $keys = array_keys($params);
            if (count($keys) && is_array($params[$keys[0]])) { // Batch inserts
                $isSingleInsert = false;
                if ((2 === count($params))
                        && is_array($params[1])
                        && (0 < count($params[1]))
                        && isset($params[1][0])
                        && is_array($params[1][0])) { // Batch insert with field names listed in a separate array
                    $query = new BatchSeparatelyNamedInsertQuery($this->db(), $this->name());
                } else {
                    $keys = array_keys($params[$keys[0]]);

                    $query = (!count($keys) || is_numeric($keys[0]))
                        ? new BatchInsertQuery($this->db(), $this->name()) // Batch insert
                        : new BatchNamedInsertQuery($this->db(), $this->name()); // Batch insert with field names
                }
            } else { // Single inserts
                $query = (!count($keys) || is_numeric($keys[0]))
                    ? new SingleInsertQuery($this->db(), $this->name()) // By order, without field names
                    : new SingleNamedInsertQuery($this->db(), $this->name()); // By field names
            }
        } else {
            throw new DbException('Invalid assignment type (must be array).');
        }

        $query->setLogger($this->logger());

        $statement = $query->run($params);

        return $isSingleInsert
            ? $this->db()->id()
            : $statement->rowCount();
    }

    public function update(array $params, $fieldName = null, $fieldValue = null, array $options = array())
    {
        $query = new UpdateQuery($this->db, $this->name(), $fieldName, $fieldValue);
        $query->setLogger($this->logger());

        $statement = $query->run($params, $options);

        return $statement->rowCount();
    }

    public function delete($fieldName = null, $fieldValue = null, array $options = array())
    {
        $query = new DeleteQuery($this->db, $this->name(), $fieldName, $fieldValue);
        $query->setLogger($this->logger());

        $statement = $query->run(array(), $options);

        return $statement->rowCount();
    }

    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetGet($offset)
    {
        if (!isset($this->fields[$offset])) {
            $field = new Field($this->db(), $this, $offset);
            $field->setLogger($this->logger());

            $this->fields[$offset] = $field;
        }

        return $this->fields[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->insert($value);
        } else {
            throw new DbException('Not implemented. Use Field instead of Table to update record.');
        }
    }

    public function offsetUnset($offset)
    {
        throw new Exception('Cannot unset table field.');
    }

    /**
     * Calls an user-defined (in configuration) method
     *
     * @param string $method Method name
     * @param array $args Arguments
     *
     * @return mixed Return type depends on configuration. It can be a single value, an object, an array, or an array of objects or arrays
     *
     * @throws DbException On error
     */
    public function __call($method, array $args)
    {
        $config = $this->config();
        if (isset($config[$method])) {
            $query = new CustomQuery($this->db(), $config[$method]);
            $query->setLogger($this->logger());

            return call_user_func_array(array($query, 'run'), $args);
        } else {
            $field = $this[$method];

            return $field($args[0]);
            // throw DbException::tableMethodNotImplemented($this->name(), $method);
        }
    }

    /**
     * Sets a config.
     *
     * @param array|ConfigInterface $config
     */
    public function setConfig($config)
    {
        $this->config = empty($config)
            ? array()
            : $config;
    }

    public function name()
    {
        return $this->name;
    }

    protected function db()
    {
        return $this->db;
    }

    protected function config()
    {
        return $this->config;
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

    protected function logger()
    {
        return $this->logger;
    }
}

