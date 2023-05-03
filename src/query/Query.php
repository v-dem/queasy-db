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

        if (null === $options) {
            $options = array();
        }

        $statement = $this->pdo()->prepare($this->sql(), $options);
        $statement->closeCursor(); // Avoid error with not closed recordset

        $counter = 1;
        foreach ($params as $paramKey => $paramValue) {
            // Detect parameter type
            $paramType = $this->getParamType($paramValue);

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

