<?php

namespace queasy\db\query;

use PDO;

use queasy\db\DbException;

class Query extends AbstractQuery
{
    /**
     * Executes SQL query.
     *
     * @param mixed $args Query arguments, can be an array or a list of function arguments
     *
     * @return int Number of affected rows or 0 for SELECT queries
     *
     * @throws DbException On error
     */
    public function run($args = null)
    {
        $args = array();
        if (func_num_args() > 0) {
            if (is_array(func_get_arg(0))) { // Check if params passed as an array (key-value pairs), other args are ignored in this case
                $args = func_get_arg(0);
            } else { // Params passed as a list of args
                $args = func_get_args();
            }
        }

        $this->statement()->closeCursor(); // To avoid some issues with unclosed cursor

        $counter = 1;
        foreach ($args as $paramKey => $paramValue) {
            // Detect parameter type
            if (is_int($paramValue)) {
                $paramType = PDO::PARAM_INT;
            } else {
                if (is_float($paramValue)) {
                    $paramValue = strval($paramValue);
                }

                $paramType = PDO::PARAM_STR;
            }

            if (is_int($paramKey)) { // Use counter as a key if params keys are numeric (so query string use question mark placeholders)
                $bindKey = $counter;

                $counter++;
            } else { // Use keys and prepend them with ":" when needed (use named placeholders)
                $bindKey = (strlen($paramKey) && (':' === $paramKey{0}))
                    ? $paramKey
                    : ':' . $paramKey;
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

