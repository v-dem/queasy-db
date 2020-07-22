<?php

namespace queasy\db\query;

use PDO;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use queasy\db\DbException;

abstract class AbstractQuery implements QueryInterface, LoggerAwareInterface
{
    private $pdo;

    private $sql;

    /**
     * Logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

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
        $this->logger = new NullLogger();

        $this->pdo = $pdo;
        $this->setSql($sql);
    }

    abstract public function run(array $params = array(), array $options = array());

    public function __invoke(array $params = array(), array $options = array())
    {
        return $this->run($params, $options);
    }

    /**
     * Set a logger.
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

    protected function pdo()
    {
        return $this->pdo;
    }

    protected function sql()
    {
        if (empty($this->sql)) {
            throw new DbException('SQL is empty.');
        }

        return $this->sql;
    }

    protected function setSql($sql)
    {
        $this->sql = $sql;
    }
}

