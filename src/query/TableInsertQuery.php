<?php

namespace queasy\db\query;

use \PDO;
use queasy\db\DbException;

class TableInsertQuery extends Query
{
    private $table;

    public function __construct(PDO $pdo, $table)
    {
        $this->table = $table;

        parent::__construct($pdo, '');
    }

    /**
     * Build SQL query.
     *
     * @param string $args Query arguments, can be an array or a list of function arguments
     *
     * @return int Number of affected rows or 0 for SELECT queries
     *
     * @throws DbException On error
     */
    public function execute($args = null)
    {
    }

    /**
     * Inserts one record into table
     *
     * @param array $fields Associative array, keys are column names
     *
     * @return integer Inserted record id
     */
    public function insert($fields = null)
    {
        $fieldsPrepared = array();
        $params = array();
        foreach ($fields as $field => $value) { // Will loop through both arrays and objects
            $fieldsPrepared[$field] = $value;
            $params[(strlen($field) && (':' === $field{0}))? $field: ':' . $field] = 1;
        }

        $paramNames = implode(', ', array_keys($params));

        $fieldNames = '';
        if (!empty($fields)) {
            $fieldNames = '`' . implode('`, `', array_keys($fields)) . '`';
        }

        $this->db->execute(
            sprintf('
                INSERT  INTO `%s` (%s)
                VALUES  (%s)',
                $this->name,
                $fieldNames,
                $paramNames
            ),
            $fieldsPrepared,
            false
        );

        return $this->db->id();
    }

    /**
     * Inserts many records into table in one query
     *
     * @param array $rows Array of associative arrays where keys are column names
     *
     * @return integer Last inserted record id
     */
    public function batchInsert(array $rows = array())
    {
        if (empty($rows)) {
            return;
        }
     
        $fieldNames = null;
            
        $normParams = array();
        $paramNames = '';
        $counter = 0;
        foreach ($rows as $row) {
            if (is_null($fieldNames)) {
                if (is_array($row)) {
                    $fieldNames = array_keys($row);
                } else if (is_object($row)) {
                    $fieldNames = get_object_vars($row);
                } else {
                    throw new DbException('Incorrect row type. Must be array or object.');
                }
            }

            $paramNames .= ((0 < $counter)? ', ': '') . '(';

            $nextParamNames = array();
            foreach ($fieldNames as $field) {
                $paramKey = (strlen($field) && (':' === $field{0}))? $field: ':' . $field;
                if (is_array($row)) {
                    $paramValue = $row[$field];
                } else if (is_object($row)) {
                    $paramValue = $row->$field;
                } else {
                    throw new DbException('Incorrect row type. Must be array or object.');
                } 
                    
                $nextParamNames[] = $paramKey . $counter;
                $normParams[$paramKey . $counter] = $paramValue;
            }
                
            $paramNames .= implode(', ', $nextParamNames);
            $paramNames .= ')';

             
            $counter++;
        }

        if (count($fieldNames)) {
            $fieldNames = '`' . implode('`, `', $fieldNames) . '`';
        } else {
            $fieldNames = '';
        }

        $this->db->execute(
            sprintf('
                INSERT  INTO `%s` (%s)
                VALUES  %s',
                $this->name,
                $fieldNames,
                $paramNames
            ),
            $normParams
        );
    }

}

