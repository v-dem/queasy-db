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

    public function __call($method)
    {
        if (isset($this->config[$method])) {
            $query = $this->config[$method]['query']; // TODO:
        } else {
            throw new DbException(sprintf('Method "%s" not implemented for table "%s".', $method, $this->name));
        }
    }

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

    public function get($fieldName, $value)
    {
        $rows = $this->select($fieldName, $value);

        return array_shift($rows);
    }

    public function insert($fields = null)
    {
        $fieldsPrepared = array();
        $params = array();
        foreach ($fields as $field => $value) { // Will loop through both arrays and objects
            $fieldsPrepared[$field] = $value;
            $params[(strlen($field) && (':' === $field{0}))? $field: ':' . $field] = 1;
        }

        $paramNames = implode(', ', $params);

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
                $fieldNames = array();
                foreach ($row as $field => $value) {
                    
                }
            }

            $paramNames .= ((0 < $counter)? ',': '') . '(';

            $nextParamNames = array();
            foreach($row as $paramKey => $paramValue) {
                $nextParamNames[] = ':' . $paramKey . $counter;
                $normParams[':' . $paramKey . $counter] = $paramValue;
            }

            $paramNames .= implode(',', $nextParamNames);
            $paramNames .= ')';

            $counter++;
        }

        $this->db->execute(
            sprintf('
                INSERT  INTO `%s` %s
                VALUES  %s',
                $this->name,
                $fieldNames,
                $paramNames
            ),
            $normParams,
            false
        );
    }

    public function update($fieldName, $fieldValue, array $updateFields, $updateAll = false)
    {
        if (is_null($fieldName)) {
            if(!$updateAll) {
                throw new DbException('Attempt to update all table records without confirmation.');
            }

            $sqlWhere = '';
        } else {
            $sqlWhere = sprintf('WHERE `%s` = :%s', $fieldName, $fieldName);
        }

        $normUpdateFields = $this->db->normalizeParams($updateFields);
        $sqlSetRows = array();
        foreach ($updateFields as $updateFieldName => $updateFieldValue) {
            $sqlSetRows[] = sprintf('`%s` = :%s', $updateFieldName, $updateFieldName);
        }

        $sqlSet = implode(', ', $sqlSetRows);

        $command = $this->db->pdo()->prepare(
            $sql = sprintf('
                UPDATE  `%s`
                SET     %s
                %s',
                $this->name,
                $sqlSet,
                $sqlWhere
            )
        );

        $command->closeCursor();

        foreach ($normUpdateFields as $updateFieldName => $updateFieldValue) {
            if (is_array($updateFieldValue)
                    && isset($updateFieldValue['type'])
                    && isset($updateFieldValue['value'])) {
                $command->bindValue($updateFieldName, $updateFieldValue['value'], $updateFieldValue['type']);
            } else {
                $command->bindValue($updateFieldName, $updateFieldValue);
            }
        }

        if (!empty($sqlWhere)) {
            $command->bindValue(':' . $fieldName, $fieldValue);
        }

        if (!$command->execute()) {
            throw new DbException('Db::update(): Can\'t execute query.');
        }
    }

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

