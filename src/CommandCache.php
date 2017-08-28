<?php

namespace queasy\db;

class CommandCache
{

    private static $instances = array();

    public static function instance(\PDO $pdo, $name = 'default')
    {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new __CLASS__($pdo);
        }

        return self::$instances[$name];
    }

    private $pdo;
    private $cache = array();

    private function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get($query)
    {
        if (!isset($this->cache[$query])) {
            $this->cache[$query] = $this->pdo()->prepare($query);
        }

        $this->cache[$query]->closeCursor();

        return $this->cache[$query];
    }

    protected function pdo()
    {
        return $this->pdo;
    }

}

