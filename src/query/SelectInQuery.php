<?php

namespace queasy\db\query;

use PDO;

class SelectInQuery extends SelectQuery
{
    private $tableName;

    private $fieldName;

    public function __construct(PDO $db, $tableName, $fieldName)
    {
        parent::__construct($db);

        $this->tableName = $tableName;
        $this->fieldName = $fieldName;
    }

    /**
     * Executes SQL query and returns all selected rows.
     *
     * @param array $params Query parameters
     *
     * @return array Returned data depends on query, usually it is an array (empty array for queries like INSERT, DELETE or UPDATE)
     *
     * @throws DbException On error
     */
    public function run(array $params)
    {
        $db = $this->db();

        $quotedParams = array_map(function($item) use($db) {
            return $db->quote($item, $this->getParamType($item));
        }, $params);

        $paramsStr = implode(', ', $quotedParams);

        $sql = sprintf('
            SELECT  *
            FROM    `%s`
            WHERE   `%s` IN (%s)',
            $this->tableName,
            $this->fieldName,
            $paramsStr
        );

        $this->setQuery($sql);

        parent::run();

        return $this->statement()->fetchAll();
    }
}

