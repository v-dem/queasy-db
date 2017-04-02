<?php

namespace queasy\db;

trait TableTrait
{

    private static function table()
    {
        $tableName = self::TABLE_NAME;

        return DB::getInstance()->$tableName;
    }

}

