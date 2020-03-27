<?php

namespace queasy\db\query;

use PDO;

class TableUpdateQuery extends TableQuery
{
    private $conditionsString;

    private $keyParams;

    public function __construct(PDO $db, $tableName, array $keyParams = array())
    {
        parent::__construct($db, $tableName);

        $this->keyParams = $keyParams;

        $this->conditionsString = implode(
            ' AND ',
            array_map(
                function($paramName) {
                    return sprintf('`%s` = :%s', $paramName, $paramName);
                },
                array_keys($keyParams)
            )
        );
    }

    public function run(array $params = array(), array $options = array())
    {
        $fixedParams = array();
        foreach ($params as $column => $value) {
            while (isset($this->keyParams[$column])) {
                $column .= '_t';
            }

            $fixedParams[$column] = $value;
        }

        $paramsString = implode(
            ', ',
            array_map(
                function($paramName) {
                    return sprintf('`%s` = :%s', $paramName, $paramName);
                },
                array_keys($fixedParams)
            )
        );

        $query = sprintf(
            'UPDATE `%s` SET %s %s',
            $this->tableName(),
            $paramsString,
            empty($this->conditionsString)
                ? ''
                : ' WHERE ' . $this->conditionsString
        );

        $this->setQuery($query);

        return parent::run(array_merge($fixedParams, $this->keyParams), $options);
    }
}

