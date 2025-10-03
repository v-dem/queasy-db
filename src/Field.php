<?php

namespace queasy\db;

use PDO;
use ArrayAccess;

class Field implements ArrayAccess
{
    protected $db;

    protected $table;

    protected $name;

    public function __construct(Db $db, Table $table, $name)
    {
        $this->db = $db;
        $this->table = $table;
        $this->name = $name;
    }

    public function update($offset, $value, array $options = array())
    {
        if (null === $value) {
            return $this->delete($offset);
        }

        return $this->table->update($value, $this->name, $offset, $options, 123);
    }

    public function delete($offset, array $options = array())
    {
        return $this->table->delete($this->name, $offset, $options);
    }

    public function select($value, $columns = array(), array $options = array())
    {
        $builder = $this->table->where()->options($options);

        if (is_array($value)) {
            $inExpr = $this->db->inExpr($this->name, $value);

            $builder = $builder->where($inExpr, $inExpr->getBindings());
        } else {
            $builder = $builder->where(sprintf('"%1$s" = :%1$s', $this->name), [
                $this->name => $value
            ]);
        }

        return $builder->select($columns);
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return null != $this->table->where(sprintf('"%1$s" = :%1$s', $this->name), [ $this->name => $offset ])
            ->select([ $this->db->expr('1') ])
            ->fetch();
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $rows = $this->select($offset)->fetchAll();

        if (is_array($offset)) {
            return $rows;
        }

        return array_shift($rows);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->update($offset, $value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    public function __invoke($value, array $options = array())
    {
        return $this->select([$value], $options)->fetchAll();
    }
}

