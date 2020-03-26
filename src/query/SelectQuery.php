<?php

namespace queasy\db\query;

class SelectQuery extends Query
{
    /**
     * Executes SQL query and returns all selected rows.
     *
     * @param array $params Query parameters
     *
     * @return array Returned data depends on query, usually it is an array (empty array for queries like INSERT, DELETE or UPDATE)
     *
     * @throws DbException On error
     */
    public function run(array $params = array(), array $options = array())
    {
        parent::run($params, $options);

        return $this->statement()->fetchAll();
    }
}

