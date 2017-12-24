<?php

namespace queasy\db\query;

class Get extends SelectQuery
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
    public function execute()
    {
        $rows = call_user_func_array('parent::execute', func_get_args());

        return array_shift($rows);
    }
}

