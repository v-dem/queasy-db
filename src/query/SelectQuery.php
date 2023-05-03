<?php

namespace queasy\db\query;

class SelectQuery extends TableQuery
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
    public function run(array $params = array(), array $options = array(), array $columns = array())
    {
        $columnsStr = '*';
        if (count($columns)) {
            $columnsStr = implode(', ', array_map(function($column) {
                return '"' . $column . '"';
            }, $columns));
        }

        $sql = sprintf('
            SELECT  %s
            FROM    "%s"',
            $columnsStr,
            $this->tableName()
        );

        $paramKeys = array_keys($params);
        $paramValues = array_values($params);

        if (count($paramKeys)) {
            if (is_array($paramValues[0]) && (1 < count($paramValues[0]))) {
                $values = $paramValues[0];

                $params = [];
                for ($i = 1; $i <= count($values); $i++) {
                    $params[':' . $paramKeys[0] . '_' . $i] = $values[$i - 1];
                }

                $sql = sprintf('
                    %s
                    WHERE   "%s" IN (%s)',
                    $sql,
                    $paramKeys[0],
                    implode(', ', array_keys($params))
                );
            } else {
                if (is_array($paramValues[0])) {
                    $params[$paramKeys[0]] = array_shift($paramValues[0]);
                }

                $sql = sprintf('
                    %s
                    WHERE   "%s" = :%2$s',
                    $sql,
                    $paramKeys[0]
                );
            }
        }

        $this->setSql($sql);

        return parent::run($params, $options);
    }
}

