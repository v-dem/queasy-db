<?php

namespace queasy\db;

trait DBTrait
{

    private static function db()
    {
        return DB::getInstance();
    }

}

