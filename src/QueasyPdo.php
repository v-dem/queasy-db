<?php

namespace queasy\db;

use PDO;

class QueasyPdo extends PDO
{
    private $statements = array();

    public function prepare($query, $options = null)
    {
        $statement = $this->statement($query, $options);
        $statement->closeCursor();

        return $statement;
    }

    protected function statement($query, $options = null)
    {
        if (!isset($this->statements[$query])) {
            $this->statements[$query] = parent::prepare($query, is_null($options)? array(): $options);
        }

        return $this->statements[$query];
    }
}

