<?php

namespace queasy\db\query;

class ConditionBuilder
{
    private $conditions;

    public function __construct(array $conditions = array())
    {
        $this->conditions = $conditions;
    }

    public function build()
    {
        $result = '';
        if (count($this->conditions)) {
            $conditionStrings = array();
            foreach ($this->conditions as $fieldName) {
                $conditionStrings[] = sprintf('`%1$s` = :%1$s', $fieldName);
            }

            $result = ' WHERE ' . implode(' AND ', $conditionStrings) . ' ';
        }

        return $result;
    }

    protected function conditions()
    {
        return $this->conditions;
    }
}

