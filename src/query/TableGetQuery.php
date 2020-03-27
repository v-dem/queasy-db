<?php

namespace queasy\db\query;

class TableGetQuery extends TableSelectQuery
{
    /**
     * Execute SQL query and return selected row or null.
     *
     * @param array $params Query parameters
     *
     * @return array|null Row or null if row does not exist
     *
     * @throws DbException On error
     */
    public function run(array $params = array(), array $options = array())
    {
        $result = parent::run($params, $options);

        return array_shift($result);
    }
}

