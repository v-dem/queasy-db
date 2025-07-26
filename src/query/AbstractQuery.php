<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\DbException;

abstract class AbstractQuery implements QueryInterface
{
    private $db;

    private $sql;

    /**
     * Constructor.
     *
     * @param queasy\db\Db $db Db instance
     * @param string $query Query string
     *
     * @throws DbException When query can't be prepared
     */
    public function __construct(Db $db, $sql = null)
    {
        $this->db = $db;
        $this->setSql($sql);
    }

    abstract public function run(array $params = array(), array $options = array());

    public function __invoke(array $params = array(), array $options = array())
    {
        return $this->run($params, $options);
    }

    protected function db()
    {
        return $this->db;
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

