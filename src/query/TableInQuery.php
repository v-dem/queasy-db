<?php

namespace queasy\db\query;

use PDO;

class TableInQuery extends TableQuery
{
    private $fieldName;

    public function __construct(PDO $db, $fieldName)
    {
        parent::__construct($db);

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
    public function run(array $params = array())
    {
        $db = $this->db();

        $inParams = array_map(
            function($item) {
                return ':val' . $item;
            },
            array_keys($params)
        );

        $sql = sprintf('
            SELECT  *
            FROM    `%s`
            WHERE   `%s` IN (%s)',
            $this->tableName(),
            $this->fieldName,
            implode(', ', $inParams)
        );

        $this->setQuery($sql);

        parent::run(array_combine($inParams, $params));

        return $this->statement()->fetchAll();
    }
}

