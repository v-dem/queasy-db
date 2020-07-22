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
        $this->logger()->debug(sprintf('%s::run(): SQL: %s', get_class($this), $this->sql()), $params);

        $statement = $this->pdo()->prepare($this->sql(), $options);
        $statement->closeCursor(); // Avoid error with not closed recordset

        $counter = 1;
        foreach ($params as $paramKey => $paramValue) {
            // Detect parameter type
            $paramType = $this->getParamType($paramValue);
/*
            if (is_int($paramKey)) { // Use counter as a key if param keys are numeric (so query string use question mark placeholders)
                $bindKey = $counter;

                $counter++;
            } else { // Use param key as a bind key (use named placeholders)
                $bindKey = Strings::startsWith($paramKey, ':')? $paramKey: ':' . $paramKey;
            }
*/
            $bindKey = is_int($paramKey)
                ? $counter++
                : (Strings::startsWith($paramKey, ':')
                    ? $paramKey
                    : ':' . $paramKey
                );

            $statement->bindValue(
                $bindKey,
                $paramValue,
                $paramType
            );
        }

        if (!$statement->execute()) {
            list($sqlErrorCode, $errorMessage, $errorMessage) = $statement->errorInfo();

            throw new DbException(sprintf('Statement failed to execute. Error code: "%s", error message: "%s".', $sqlErrorCode, $errorMessage));
        }

        return $statement;
    }

    protected function getParamType($value)
    {
        if (null === $value) {
            return PDO::PARAM_NULL;
        }

        if (is_int($value)) {
            return PDO::PARAM_INT;
        }

        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }

        return PDO::PARAM_STR;
    }
}

