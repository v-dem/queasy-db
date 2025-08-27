<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\Expression;
use queasy\db\DbException;

class QueryBuilder extends TableQuery
{
    protected $intoTable;

    protected $where;

    protected $bindings = array();

    protected $joins = array();

    protected $options = array();

    public function options(array $options = array())
    {
        $this->options = $options;

        return $this;
    }

    public function join($columnOrJoinString, $joinTable, $joinedColumn, $type = 'INNER', $operator = '=')
    {
        $this->joins[] = (1 === func_num_args())
            ? $columnOrJoinString
            : "$type JOIN \"$joinTable\"
                ON \"" . $this->table() . "\".\"$columnOrJoinString\" $operator \"$joinTable\".\"$joinedColumn\"";

        return $this;
    }

    public function innerJoin($column, $joinTable, $joinedColumn)
    {
        return $this->join($column, $joinTable, $joinedColumn, 'INNER');
    }

    public function leftJoin($column, $joinTable, $joinedColumn)
    {
        return $this->join($column, $joinTable, $joinedColumn, 'LEFT');
    }

    public function rightJoin($column, $joinTable, $joinedColumn)
    {
        return $this->join($column, $joinTable, $joinedColumn, 'RIGHT');
    }

    public function fullJoin($column, $joinTable, $joinedColumn)
    {
        return $this->join($column, $joinTable, $joinedColumn, 'FULL OUTER');
    }

    public function where($where = null, array $bindings = array())
    {
        $this->where = $where;
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

/*
OFFSET offset_value ROWS:
FETCH FIRST/NEXT fetch_value ROWS ONLY
*/

/*
    // Set grouping (GROUP BY clause)
    public function groupBy($column)
    {
        $this->groupBy = 'GROUP BY ';

        return $this;
    }

    // Add HAVING clause
    public function having($conditions, $bindings)
    {
        $this->havingConditions = $conditions;
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }
*/

/*
    // Set ordering (ORDER BY clause)
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy = 'ORDER BY ';
        if (is_array($column)) {
            $columns = array_map(function($key, $value) {
                return is_numeric($key)
                    ? $value . ' ASC'
                    : $key . ' ' . $value;
            }, array_keys($column), array_values($column));

            $this->orderBy = implode(', ', $columns);
        } else {
            $this->orderBy = $column . ' ' . $direction;
        }

        return $this;
    }
*/

    public function into($intoTable)
    {
        if (!empty($this->intoTable)) {
            throw new DbException('INTO clause already created');
        }

        $this->intoTable = $intoTable;

        return $this;
    }

    public function buildJoins()
    {
        $sql = '';
        if (!empty($this->joins)) {
            $sql .= '
        ' . implode('
        ', $this->joins);
        }

        return $sql;
    }

    public function buildWhere()
    {
        $sql = '';
        if (!empty($this->where)) {
            $sql .= "
WHERE   " . $this->where;
        }
/*
        if (!empty($this->order)) {
            $query .= " {$this->order}";
        }

        if (!empty($this->limit)) {
            $query .= " {$this->limit}";
        }
*/
        return $sql;
    }

    public function insert(array $params = array())
    {
        if (empty($this->intoTable)) {
            throw new DbException('Missing INTO clause');
        }

        $columns = array();
        $selects = array();
        foreach ($params as $paramName => $paramValue) {
            if (!is_int($paramName)) {
                $columns[] = '"' . $paramName . '"';
            }

            $paramValueStr = ':' . $paramName;
            if ($paramValue instanceof Expression) {
                $paramValueStr = $paramValue->getExpression();

                $this->bindings = array_merge($this->bindings, $paramValue->getBindings());
            } else {
                $this->bindings[$paramName] = $paramValue;
            }

            $selects[] = $paramValueStr;
        }

        $columnsStr = empty($columns)
            ? ''
            : ' (' . implode(', ', $columns) . ')';

        $selectsStr = empty($selects)
            ? '*'
            : implode(', ', $selects);

        $sql = sprintf('
INSERT  INTO "%1$s"%2$s
SELECT  %3$s
FROM    "%4$s"%5$s', $this->intoTable, $columnsStr, $selectsStr, $this->table(), $this->buildJoins() . $this->buildWhere());

        $this->setSql($sql);

        return $this->run($this->bindings, $this->options);
    }

    public function update(array $params = array())
    {
        if (!empty($this->intoTable)) {
            throw new DbException('INTO clause could not be used for UPDATE');
        }

        $sets = array();
        foreach ($params as $paramName => $paramValue) {
            $paramValueStr = ':' . $paramName;
            if ($paramValue instanceof Expression) {
                $paramValueStr = $paramValue->getExpression();

                $this->bindings = array_merge($this->bindings, $paramValue->getBindings());
            } else {
                $this->bindings[$paramName] = $paramValue;
            }

            $sets[] = sprintf('"%1$s" = %2$s', $paramName, $paramValueStr);
        }

        $sql = sprintf('
UPDATE  "%1$s"%2$s
SET     %3$s%4$s', $this->table(), $this->buildJoins(), implode(',' . PHP_EOL . '        ', $sets), $this->buildWhere());

        $this->setSql($sql);

        return $this->run($this->bindings, $this->options);
    }

    public function delete()
    {
        if (!empty($this->intoTable)) {
            throw new DbException('INTO clause could not be used for DELETE');
        }

        $sql = sprintf('
DELETE  FROM "%1$s"%2$s%3$s', $this->table(), $this->buildJoins(), $this->buildWhere());

        $this->setSql($sql);

        return $this->run($this->bindings, $this->options);
    }

    public function select(array $params = array())
    {
        if (!empty($this->intoTable)) {
            throw new DbException('INTO clause could not be used for SELECT this way');
        }

        $selects = array();
        foreach ($params as $paramName => $paramValue) {
            $selects[] = is_int($paramName)
                ? (($paramValue instanceof Expression)
                    ? $paramValue->getExpression()
                    : '"' . $paramValue . '"')
                : ((($paramValue instanceof Expression)
                    ? $paramValue->getExpression()
                    : '"' . $paramValue . '"')
                    . ' AS "' . $paramName . '"');

            if ($paramValue instanceof Expression) {
                $this->bindings = array_merge($this->bindings, $paramValue->getBindings());
            }
        }

        $sql = sprintf('
SELECT  %1$s
FROM    %2$s%3$s%4$s', empty($selects) ? '*' : implode(', ', $selects), $this->table(), $this->buildJoins(), $this->buildWhere());

        $this->setSql($sql);

        return $this->run($this->bindings, $this->options);
    }
}

