<?php

namespace queasy\db;

trait TableTrait
{

    private static function table()
    {
        $tableName = self::TABLE_NAME;

        return Db::getInstance()->$tableName;
    }

}

