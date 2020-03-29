<?php

namespace queasy\db\query;

use PDO;

class DeleteQuery extends TableQuery
{
    private $fieldName;

    private $fieldValue;

    public function __construct(PDO $db, $tableName, $fieldName = null, $fieldValue = null)
    {
        parent::__construct($db, $tableName);

        $this->fieldName = $fieldName;

        $this->fieldValue = is_array($fieldValue)
            ? array_unique($fieldValue)
            : $fieldValue;
    }

    public function run(array $params = array(), array $options = array())
    {
        $conditionString = '';
        if (null !== $this->fieldName) {
            if (is_array($this->fieldValue)) {
                $params = array();
                for ($i = 1; $i <= count($this->fieldValue); $i++) {
                    $params[':' . $this->fieldName . '_queasydb_' . $i] = $this->fieldValue[$i - 1];
                }

                $conditionString = sprintf(
                    'WHERE %s IN (%s)',
                    $this->fieldName,
                    implode(', ', array_keys($params))
                );
            } else {
                $conditionString = sprintf(
                    'WHERE `%s` = :%s',
                    $this->fieldName,
                    $this->fieldName
                );

                $params = array(':' . $this->fieldName => $this->fieldValue);
            }
        }

        $sql = sprintf('
            DELETE  FROM `%s`
            %s',
            $this->tableName(),
            $conditionString
        );

        $this->setQuery($sql);

        return parent::run($params, $options);
    }
}

