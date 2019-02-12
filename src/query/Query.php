<?php

namespace queasy\db\query;

use queasy\helper\Strings;

use queasy\db\Db;
use queasy\db\DbException;

class Query extends AbstractQuery
{
    /**
     * Executes SQL query.
     *
     * @param array $params Query arguments
     *
     * @return int Number of affected rows for UPDATE/INSERT/DELETE queries or queasy\db\Statement instance for SELECT queries
     *
     * @throws DbException On error
     */
    public function run(array $params = array())
    {
        /*
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
        */

        $counter = 1;
        foreach ($params as $paramKey => $paramValue) {
            // Detect parameter type
            $paramType = $this->getParamType($paramValue);

            if (is_int($paramKey)) { // Use counter as a key if param keys are numeric (so query string use question mark placeholders)
                $bindKey = $counter;

                $counter++;
            } else { // Use param key as a bind key (use named placeholders)
                $bindKey = Strings::startsWith($paramKey, ':')? $paramKey: ':' . $paramKey;
            }

            $this->statement()->bindValue(
                $bindKey,
                $paramValue,
                $paramType
            );
        }

        if (!$this->statement()->execute()) {
            list($sqlErrorCode, $driverErrorCode, $errorMessage) = $this->statement()->errorInfo();

            throw DbException::cannotExecuteQuery($this->query(), $sqlErrorCode, $errorMessage);
        }

        return ($this->statement()->columnCount())
            ? $this->statement()
            : $this->statement()->rowCount();
    }

    protected function getParamType($value)
    {
        if (is_null($value)) {
            $paramType = Db::PARAM_NULL;
        } elseif (is_int($value)) {
            $paramType = Db::PARAM_INT;
        } elseif (is_bool($value)) {
            $paramType = Db::PARAM_BOOL;
        } else {
            if (is_float($value)) {
                $value = strval($value);
            }

            $paramType = Db::PARAM_STR;
        }

        return $paramType;
    }
}

