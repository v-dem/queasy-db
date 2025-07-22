<?php

namespace queasy\db\query;

use PDO;

use queasy\db\DbException;

abstract class AbstractQuery implements QueryInterface
{
    private $pdo;

    private $sql;

    /**
     * Constructor.
     *
     * @param PDO $pdo PDO instance
     * @param string $query Query string
     *
     * @throws DbException When query can't be prepared
     */
    public function __construct(PDO $pdo, $sql = null)
    {
        $this->pdo = $pdo;
        $this->setSql($sql);
    }

    abstract public function run(array $params = array(), array $options = array());

    public function __invoke(array $params = array(), array $options = array())
    {
        return $this->run($params, $options);
    }

    protected function pdo()
    {
        return $this->pdo;
    }

    protected function sql()
    {
        if (empty($this->sql)) {
            throw new DbException('SQL string is empty.');
        }

        return $this->sql;
    }

    protected function setSql($sql)
    {
        $this->sql = $sql;
    }
}

