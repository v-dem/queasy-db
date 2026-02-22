<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\Expression;
use queasy\db\DbException;

class QueryBuilder extends TableQuery
{
    protected $intoTable;

    protected $where;

    protected $having;

    protected $bindings = array();

    protected $joins = array();

    protected $orders = array();

    protected $groups = array();

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
        return $this->join($column, $joinTable, $joinedColumn);
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

    public function order($column, $direction = 'ASC')
    {
        $this->orders[$column] = $direction;

        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        return $this->order($column, $direction);
    }

    public function having($having = null, array $bindings = array())
    {
        $this->having = $having;
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    public function group($column)
    {
        $this->groups[] = $column;

        return $this;
    }

    public function groupBy($column)
    {
        return $this->group($column);
    }

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
        if (!empty($this->joins)) {
            return '
        ' . implode('
        ', $this->joins);
        }

        return '';
    }

    public function buildWhere()
    {
        if (!empty($this->where)) {
            return "
WHERE   " . $this->where;
        }

        return '';
    }

    public function buildOrders()
    {
        if (count($this->orders)) {
            return '
ORDER   BY ' . implode(', ', array_map(static function($column, $direction) {
                return '"' . $column . '" ' . $direction;
            },
            array_keys($this->orders), array_values($this->orders)));
        }

        return '';
    }

    public function buildHaving()
    {
        if (!empty($this->having)) {
            return "
HAVING  " . $this->having;
        }

        return '';
    }

    public function buildGroups()
    {
        if (count($this->groups)) {
            return '
GROUP   BY ' . implode(', ', $this->groups);
        }

        return '';
    }

    public function insert(array $params = array())
    {
        if (empty($this->intoTable)) {
            throw new DbException('Missing INTO clause');
        }

        $columns = array();
        $selects = array();
        foreach ($params as $name => $value) {
            if (!is_int($name)) {
                $columns[] = '"' . $name . '"';
            }

            $valueStr = ':' . $name;
            if ($value instanceof Expression) {
                $valueStr = $value->getExpression();

                $this->bindings = array_merge($this->bindings, $value->getBindings());
            } else {
                $this->bindings[$name] = $value;
            }

            $selects[] = $valueStr;
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
        foreach ($params as $name => $value) {
            $valueStr = ':' . $name;
            if ($value instanceof Expression) {
                $valueStr = $value->getExpression();

                $this->bindings = array_merge($this->bindings, $value->getBindings());
            } else {
                $this->bindings[$name] = $value;
            }

            $sets[] = sprintf('"%1$s" = %2$s', $name, $valueStr);
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
        foreach ($params as $name => $value) {
            $selects[] = is_int($name)
                ? (($value instanceof Expression)
                    ? $value->getExpression()
                    : '"' . $value . '"')
                : ((($value instanceof Expression)
                    ? $value->getExpression()
                    : '"' . $value . '"')
                    . ' AS "' . $name . '"');

            if ($value instanceof Expression) {
                $this->bindings = array_merge($this->bindings, $value->getBindings());
            }
        }

        $sql = sprintf('
SELECT  %1$s
FROM    %2$s%3$s%4$s%5$s%6$s%7$s',
            (empty($selects) ? '*' : implode(', ', $selects)),
            $this->table(),
            $this->buildJoins(),
            $this->buildWhere(),
            $this->buildOrders(),
            $this->buildGroups(),
            $this->buildHaving());

        $this->setSql($sql);

        return $this->run($this->bindings, $this->options);
    }
}

