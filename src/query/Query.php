<?php

namespace queasy\db\query;

use PDO;

use queasy\db\DbException;

class Query extends AbstractQuery
{
    public function params(array $args = array())
    {
        if (count($args)) {
            

            return array();
        }

        return $args;
    }

    /**
     * Executes SQL query.
     *
     * @param mixed $args Query arguments, can be an array or a list of function arguments
     *
     * @return int Number of affected rows or 0 for SELECT queries
     *
     * @throws DbException On error
     */
    public function run()
    {
        $args = array();
        if (func_num_args() > 0) {
            if (is_array(func_get_arg(0))) { // Check if params passed as an array (key-value pairs), other args are ignored in this case
                $args = func_get_arg(0);
            } else { // Params passed as a list of args
                $args = func_get_args();
            }
        }

        $argKeys = array_keys($args);
        while ((1 == count($args)) && (is_array($args[$argKeys[0]]))) {
            $args = $args[0];

            $argKeys = array_keys($args);
        }

        $counter = 1;
        foreach ($args as $paramKey => $paramValue) {
            if (is_int($paramValue)) { // Detect parameter type
                $paramType = PDO::PARAM_INT;
            } else {
                if (is_float($paramValue)) {
                    $paramValue = strval($paramValue);
                }

                $paramType = PDO::PARAM_STR;
            }

            if (is_int($paramKey)) { // Use counter as a key if param keys are numeric (so query string use question mark placeholders)
                $bindKey = $counter;

                $counter++;
            } else { // Use param key as a bind key (use named placeholders)
                $bindKey = $paramKey;
            }

            $this->statement()->bindValue(
                $bindKey,
                $paramValue,
                $paramType
            );
        }

        if (!$this->statement()->execute()) {
            list($sqlErrorCode, $driverErrorCode, $errorMessage) = $this->statement()->errorInfo();

            throw new DbException(sprintf('Can\'t execute query (%s: %s): %s', $sqlErrorCode, $errorMessage, $this->query()));
        }

        return $this->statement()->rowCount();
    }
}

