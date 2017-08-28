<?php

namespace queasy\db;

class Table
{

    private $db;
    private $name;
    private $config;

    public function __construct(Db $db, $name, $config = [])
    {
        $this->db = $db;
        $this->name = $name;
        $this->config = $config;
    }

    /**
     * Calls an user-defined (in configuration) method
     *
     * @param string $method Method name
     * @param array $args Arguments
     * @return mixed Return type depends on configuration. It can be a single value, an object, an array, or an array of objects or arrays
     * @throws DbException On error
     */
    public function __call($method, array $args)
    {
        if (isset($this->config[$method])) {
            $query = $this->config[$method]['query'];

            $db = $this->db;

            return call_user_func_array(array($db, 'execute'), array_merge(array($query), $args));
        } else {
            throw new DbException(sprintf('Method "%s" not implemented for table "%s".', $method, $this->name));
        }
    }

    /**
     * Returns all records from table
     *
     * @return array Selected rows
     */
    public function all()
    {
        return $this->db->select(
            sprintf('
                SELECT  *
                FROM    `%s`',
                $this->name
            )
        );
    }

    /**
     * Returns records where $fieldName column value equals to @value
     *
     * @param string $fieldName Column name
     * @param string $value Value
     * @return array Selected rows
     */
    public function select($fieldName, $value)
    {
        return $this->db->select(
            sprintf('
                SELECT  *
                FROM    `%s`
                WHERE   `%s` = :value',
                $this->name,
                $fieldName
            ),
            array(
                'value' => $value
            )
        );
    }

    /**
     * Returns one record where $fieldName column value equals to @value
     *
     * @param string $fieldName Column name
     * @param string $value Value
     * @return array Selected row
     */
    public function get($fieldName, $value)
    {
        $rows = $this->select($fieldName, $value);

        return array_shift($rows);
    }

    /**
     * Inserts one record into table
     *
     * @param array $fields Associative array, keys are column names
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

    public function update($fieldName, $fieldValue, array $fields = array())
    {
        if (is_null($fieldName)) {
            $sqlWhere = '';
        } else {
            $sqlWhere = sprintf('WHERE `%s` = :%s', $fieldName, $fieldName);
        }

        $normFields = array();
        $sqlSetRows = array();
        foreach ($fields as $field => $value) {
            $sqlSetRows[] = sprintf('`%s` = %s%s', $updateFieldName, (strlen($updateFieldName) && (':' === $updateFieldName{0}))? '': ':', $updateFieldName);
            $normFields[(strlen($updateFieldName) && (':' === $updateFieldName{0}))? '': ':'] = $value;
        }

        $sqlSet = implode(', ', $sqlSetRows);

        if (!empty($sqlWhere)) {
            $normFields[(strlen($fieldName) && (':' === $fieldName{0}))? '': ':'] = $fieldValue;
        }

        $this->db->execute(
            $sql = sprintf('
                UPDATE  `%s`
                SET     %s
                %s',
                $this->name,
                $sqlSet,
                $sqlWhere
            )
        );
    }

    /**
     * Removes record(s) where specified column matches specified value
     *
     * @param string $fieldName Field name to match rows
     * @param mixed $value Value to match
     * @return null
     */
    public function remove($fieldName, $value)
    {
        $this->db->execute(
            sprintf('
                DELETE  FROM `%s`
                WHERE   `%s` = :value',
                $this->name,
                $fieldName
            ),
            array(
                ':value' => $value
            )
        );
    }

    /**
     * Removes all records from table
     *
     * @return null
     */
    public function clear()
    {
        $this->db->execute(
            sprintf('
                DELETE  FROM `%s`',
                $this->name
            )
        );
    }

}

