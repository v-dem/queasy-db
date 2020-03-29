<?php

namespace queasy\db\query;

use PDO;

use queasy\helper\Strings;

use queasy\db\DbException;

class Query extends AbstractQuery
{
    /**
     * Executes SQL query.
     *
     * @param array $params Query arguments
     *
     * @return PDOStatement PDOStatement instance used to run query
     *
     * @throws DbException On error
     */
    public function run(array $params = array(), array $options = array())
    {
        $this->logger()->debug('Query::run(): SQL: ' . $this->sql(), $params);

        try {
            $statement = $this->db()->prepare($this->sql(), $options);
            $statement->closeCursor(); // Avoid error with not closed recordset
        } catch (Exception $e) {
            throw DbException::cannotPrepareStatement($this->query(), $e);
        }

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

            $statement->bindValue(
                $bindKey,
                $paramValue,
                $paramType
            );
        }

        if (!$statement->execute()) {
            list($sqlErrorCode, $driverErrorCode, $errorMessage) = $statement->errorInfo();

            throw DbException::cannotExecuteQuery($this->sql(), $sqlErrorCode, $errorMessage);
        }

        return $statement;
    }

    protected function getParamType($value)
    {
        if (null === $value) {
            $paramType = PDO::PARAM_NULL;
        } elseif (is_int($value)) {
            $paramType = PDO::PARAM_INT;
        } elseif (is_bool($value)) {
            $paramType = PDO::PARAM_BOOL;
        } else {
            if (is_float($value)) {
                $value = strval($value);
            }

            $paramType = PDO::PARAM_STR;
        }

        return $paramType;
    }
}

