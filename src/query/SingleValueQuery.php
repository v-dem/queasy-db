<?php

namespace queasy\db\query;

class SingleValueQuery extends GetQuery
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
        $row = parent::run(func_get_args());

        if (empty($row)) {
            throw DbException::noValueSelected($query);
        } else {
            return array_shift($row);
        }
    }
}

