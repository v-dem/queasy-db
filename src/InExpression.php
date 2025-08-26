<?php

namespace queasy\db;

class InExpression extends Expression
{
    public function __construct($columnName, array $values, $isAssoc = true)
    {
        $conditionString = '';
        $params = $values;
        if (count($values)) {
            if ($isAssoc) {
                $assocValues = array();
                for ($i = 1; $i <= count($values); $i++) {
                    $assocValues[':' . $columnName . '_IN_' . $i] = $values[$i - 1];
                }

                $conditionString = sprintf('"%s" IN (%s)', $columnName, implode(', ', array_keys($assocValues)));
                $params = $assocValues;
            } else {
                $conditionString = sprintf('"%s" IN (%s?)', $columnName, str_repeat('?, ', count($values) - 1));
            }
        }

        parent::__construct($conditionString, $params);
    }
}

