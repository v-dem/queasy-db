<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\Expression;

class UpdateQuery extends TableQuery
{
    private $fieldName;

    private $fieldValue;

    public function __construct(Db $db, $tableName, $fieldName = null, $fieldValue = null)
    {
        parent::__construct($db, $tableName);

        $this->fieldName = $fieldName;

        $this->fieldValue = is_array($fieldValue)
            ? array_unique($fieldValue)
            : $fieldValue;
    }

    public function run(array $params = array(), array $options = array())
    {
        $paramsString = implode(
            ', ',
            array_map(
                function($paramName) use(&$params) {
                    $paramValue = ':' . $paramName;
                    if (is_object($params[$paramName]) && ($params[$paramName] instanceof Expression)) {
                        $paramValue = $params[$paramName];
                        $bindings = $params[$paramName]->getBindings();
                        if (count($bindings)) {
                            $params = array_merge($params, $bindings);
                        }

                        unset($params[$paramName]);
                    }

                    return sprintf(
                        '"%s" = %s',
                        $paramName,
                        $paramValue
                    );
                },
                array_keys($params)
            )
        );

        $conditionString = '';
        if (null !== $this->fieldName) {
            if (is_array($this->fieldValue)) {
                $fieldValueParams = array();
                for ($i = 1; $i <= count($this->fieldValue); $i++) {
                    $fieldValueParams[':' . $this->fieldName . '_queasydb_' . $i] = $this->fieldValue[$i - 1];
                }

                $conditionString = sprintf(
                    '"%s" IN (%s)',
                    $this->fieldName,
                    implode(', ', array_keys($fieldValueParams))
                );

                $params = array_merge($params, $fieldValueParams);
            } else {
                $conditionString = sprintf(
                    '"%s" = :%s',
                    $this->fieldName,
                    $this->fieldName . '_queasydb' // Add a suffix to avoid collision with parameters passed to SET clause
                );

                $params[':' . $this->fieldName . '_queasydb'] = $this->fieldValue;
            }
        }

        $sql = sprintf('
            UPDATE  "%s"
            SET     %s
            %s',
            $this->tableName(),
            $paramsString,
            empty($conditionString)
                ? ''
                : 'WHERE   ' . $conditionString
        );

        $this->setSql($sql);

        return parent::run($params, $options);
    }
}

