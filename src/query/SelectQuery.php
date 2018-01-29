<?php

namespace queasy\db\query;

class SelectQuery extends Query
{
    /**
     * Executes SQL query and returns all selected rows.
     *
     * @param mixed $args Query arguments, can be an array or a list of function arguments
     *
     * @return array Returned data depends on query, usually it is an array (empty array for queries like INSERT, DELETE or UPDATE)
     *
     * @throws DbException On error
     */
    public function run()
    {
        parent::run(func_get_args());

        return $this->statement()->fetchAll();
    }
}
