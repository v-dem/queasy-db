<?php

namespace queasy\db\query;

use PDO;
use PDOException;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class AbstractQuery implements QueryInterface
{
    use LoggerAwareTrait;

    private $pdo;

    private $query;

    private $params;

    private $statement;

    /**
     * Constructor.
     *
     * @param string $query Query string
     *
     * @throws DbException When query can't be prepared
     */
    public function __construct(PDO $pdo, $query)
    {
        $this->pdo = $pdo;
        $this->query = $query;
    }

    abstract public function run();

    protected function pdo()
    {
        return $this->pdo;
    }

    protected function query()
    {
        return $this->query;
    }

    protected function setQuery($query)
    {
        $this->query = $query;
    }

    protected function statement()
    {
        if (!$this->statement) {
            try {
                $this->statement = $this->pdo()->prepare($this->query());
            } catch (PDOException $e) {
                throw new DbException(sprintf('Cannot prepare statement "%s".', $query), null, $e);
            }
        }

        return $this->statement;
    }

    protected function logger()
    {
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }
}

