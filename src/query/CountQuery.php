<?php

namespace queasy\db\query;

class CountQuery extends TableQuery
{
    /**
     * Executes SQL query and returns all selected rows.
     *
     * @param array $params Query parameters
     *
     * @return int Count of records found
     *
     * @throws DbException On error
     */
    public function run(array $params = array(), array $options = array())
    {
        if (count($params)) {
            $this->setSql(sprintf('SELECT count(*) FROM `%s` WHERE `%s` = :%2$s', $this->tableName(), key($params)));
        } else {
            $this->setSql(sprintf('SELECT count(*) FROM `%s`', $this->tableName()));
        }

        return parent::run($params, $options);
    }
}

