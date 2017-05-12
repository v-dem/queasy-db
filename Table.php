<?php

namespace queasy\db;

class Table
{

    private $db;
    private $tableName;

    public function __construct(Db $db, $tableName)
    {
        $this->db = $db;
        $this->tableName = $tableName;
    }

    public function all($fetchType = \PDO::FETCH_ASSOC)
    {
        return $this->db->select(
            sprintf('
                SELECT  *
                FROM    `%s`',
                $this->tableName
            ),
            array(),
            $fetchType
        );
    }

    public function select($fieldName, $value, $fetchType = \PDO::FETCH_ASSOC)
    {
        return $this->db->select(
            sprintf('
                SELECT  *
                FROM    `%s`
                WHERE   `%s` = :value',
                $this->tableName,
                $fieldName
            ),
            array(
                ':value' => $value
            ),
            $fetchType
        );
    }

    public function get($fieldName, $value, $fetchType = \PDO::FETCH_ASSOC)
    {
        $rows = $this->select($fieldName, $value, $fetchType);

        return array_shift($rows);
    }

    public function insert(array $fields)
    {
        // TODO: Check for ability to insert a record with empty fields
        // (for example when table contains only auto-increment field or other fields have default values)

        $normParams = $this->db->normalizeParams($fields);

        $paramNames = implode(', ', array_keys($normParams));
        $fieldNames = '`' . implode('`, `', array_keys($fields)) . '`';

        $this->db->execute(
            sprintf('
                INSERT  INTO `%s` (%s)
                VALUES  (%s)',
                $this->tableName,
                $fieldNames,
                $paramNames
            ),
            $normParams,
            false
        );

        return $this->db->pdo()->lastInsertId();
    }

    public function batchInsert(array $rows = array())
    {
        if (empty($rows)) {
            return;
        }

        $fieldNames = array_keys(Db::getInstance()->normalizeParams($rows[0]));

        $normParams = array();
        $paramNames = '';
        $counter = 0;
        foreach ($rows as $row) {
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
                $this->tableName,
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
                $this->tableName,
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
                $this->tableName,
                $fieldName
            ),
            array(
                ':value' => $value
            )
        );
    }

    public function clear()
    {
        $this->db->execute(sprintf('
            DELETE  FROM `%s`',
            $this->tableName
        ));
    }

}

