<?php

namespace queasy\db;

use InvalidArgumentException;
use BadMethodCallException;

use PDO;
use PDOException;
use ArrayAccess;
use Countable;
use IteratorAggregate;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use queasy\helper\System;

use queasy\db\query\CustomQuery;
use queasy\db\query\CountQuery;
use queasy\db\query\SelectQuery;
use queasy\db\query\UpdateQuery;
use queasy\db\query\DeleteQuery;

class Table implements ArrayAccess, Countable, IteratorAggregate, LoggerAwareInterface
{
    protected $pdo;

    protected $name;

    protected $fields;

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

    public function __construct(PDO $pdo, $name, $config = array())
    {
        $this->logger = new NullLogger();

        $this->pdo = $pdo;
        $this->name = $name;
        $this->fields = array();
        $this->config = $config;
    }

    public function __get($fieldName)
    {
        if (!isset($this->fields[$fieldName])) {
            $field = new Field($this->pdo, $this, $fieldName);
            $field->setLogger($this->logger);

            $this->fields[$fieldName] = $field;
        }

        return $this->fields[$fieldName];
    }

    public function statement()
    {
        $query = new SelectQuery($this->pdo, $this->name);
        $query->setLogger($this->logger);

        return $query();
    }

    public function all()
    {
        return $this->statement()->fetchAll();
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return $this->statement()->getIterator();
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (null !== $offset) {
            throw new BadMethodCallException('Not implemented. Use Field instead of Table to update record.');
        }

        $this->insert($value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Calls an user-defined (in configuration) method
     *
     * @param string $method Method name
     * @param array $args Arguments
     *
     * @return mixed Return type depends on configuration. It can be a single value, a stdClass object, an array, or an array of objects or arrays, or PDOStatement instance
     *
     * @throws DbException On error
     */
    public function __call($method, array $args)
    {
        if (isset($this->config[$method])) {
            $query = new CustomQuery($this->pdo, $this->config[$method]);
            $query->setLogger($this->logger);

            return System::callUserFuncArray(array($query, 'run'), $args);
        }

        throw new InvalidArgumentException("Method \"$method\" is not declared in \"{$this->name}\" table configuration.");
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        $query = new CountQuery($this->pdo, $this->name);
        $query->setLogger($this->logger);

        $statement = $query();

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return array_shift($row);
    }

    public function insert()
    {
        $params = (1 === func_num_args())
            ? func_get_arg(0)
            : func_get_args();
        if ((null === $params) || !is_array($params)) {
            throw new InvalidArgumentException('Wrong rows argument.');
        }

        $keys = array_keys($params);

        // Default is single inserts
        $isSingleInsert = true;
        $queryClass = (!count($keys) || is_numeric($keys[0]))
            ? 'queasy\\db\\query\\SingleInsertQuery' // By order, without field names
            : 'queasy\\db\\query\\SingleNamedInsertQuery'; // By field names

        if (count($keys) && is_array($params[$keys[0]])) { // Batch inserts
            $isSingleInsert = false;
            $queryClass = 'queasy\\db\\query\\BatchSeparatelyNamedInsertQuery'; // Default
            $keys = array_keys($params[$keys[0]]);
            $queryClass = (!count($keys) || is_numeric($keys[0]))
                ? 'queasy\\db\\query\\BatchInsertQuery' // Batch insert
                : 'queasy\\db\\query\\BatchNamedInsertQuery'; // Batch insert with field names
        }

        $query = new $queryClass($this->pdo, $this->name);
        $query->setLogger($this->logger);

        $statement = $query($params);

        if ($isSingleInsert) {
            try {
                return $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                return true;
            }
        }

        return $statement->rowCount();
    }

    public function update(array $params, $fieldName = null, $fieldValue = null, array $options = array())
    {
        $query = new UpdateQuery($this->pdo, $this->name, $fieldName, $fieldValue);
        $query->setLogger($this->logger);

        $statement = $query($params, $options);

        return $statement->rowCount();
    }

    public function delete($fieldName = null, $fieldValue = null, array $options = array())
    {
        $query = new DeleteQuery($this->pdo, $this->name, $fieldName, $fieldValue);
        $query->setLogger($this->logger);

        $statement = $query(array(), $options);

        return $statement->rowCount();
    }

    public function select(array $columns = null, array $options = array())
    {
        $queryBuilder = new QueryBuilder($this->pdo, $options);
        $queryBuilder->setLogger($this->logger);
        $queryBuilder->table($this->name);
        $queryBuilder->select($columns);

        return $queryBuilder;
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

    public function getName()
    {
        return $this->name;
    }
}

