<?php

namespace queasy\db\query;

use PDO;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use queasy\db\DbException;

abstract class AbstractQuery implements QueryInterface, LoggerAwareInterface
{
    private $db;

    private $query;

    private $statement;

    /**
     * Logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param PDO $db PDO instance
     * @param string $query Query string
     *
     * @throws DbException When query can't be prepared
     */
    public function __construct(PDO $db, $query = null)
    {
        $this->db = $db;
        $this->setQuery($query);
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
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    protected function db()
    {
        return $this->db;
    }

    protected function query()
    {
        if (empty($this->query)) {
            throw new DbException('Query is empty.');
        }

        return $this->query;
    }

    protected function setQuery($query)
    {
        $this->query = $query;
    }
}

