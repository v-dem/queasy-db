<?php

namespace queasy\db\query;

class GetQuery extends SelectQuery
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
        $rows = parent::run(func_get_args());

        return array_shift($rows);
    }
}

