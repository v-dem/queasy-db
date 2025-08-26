<?php

namespace queasy\db\query;

use queasy\helper\Arrays;
use queasy\helper\Strings;

use queasy\db\Db;
use queasy\db\Blob;
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
        $statement = $this->db()->prepare($this->sql(), $options);
        $statement->closeCursor(); // Avoid error with recordset not closed

        $counter = 1;
        $isAssoc = Arrays::isAssoc($params);
        foreach ($params as $paramKey => $paramValue) {
            $paramType = $this->getParamType($paramValue);

            $bindKey = $isAssoc
                ? $paramKey
                : $counter++;

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
            return Db::PARAM_NULL;
        }

        if (is_int($value)) {
            return Db::PARAM_INT;
        }

        if (is_bool($value)) {
            return Db::PARAM_BOOL;
        }

        if (is_resource($value) || ($value instanceof Blob)) {
            return Db::PARAM_LOB;
        }

        return Db::PARAM_STR;
    }
}

