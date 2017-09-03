<?php

namespace queasy\db;

trait TableTrait
{

    /**
     * Returns queasy\db\Table instance
     *
     * @return queasy\db\Table
     */
    protected static function table()
    {
        // If TABLE_NAME is not declared, use unqualified class name as a table name
        $name = defined(self::class . '::TABLE_NAME')
            ? self::TABLE_NAME
            : str_replace(__NAMESPACE__ . "\\", '', self::class);

        return Db::instance()->$name;
    }

}

