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
     * @return int|Statement Number of affected rows for UPDATE/INSERT/DELETE queries or queasy\db\Statement instance for SELECT queries
     *
     * @throws DbException On error
     */
    public function run(array $params = array())
    {
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

        return ($this->statement()->columnCount()) // Check if it was SELECT query
            ? $this->statement() // And return Statement if yes
            : $this->statement()->rowCount(); // Or return number of affected rows if no
    }

    protected function getParamType($value)
    {
        if (null === $value) {
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

