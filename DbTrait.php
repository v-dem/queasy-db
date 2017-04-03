<?php

namespace queasy\db;

trait DbTrait
{

    private static function db()
    {
        return Db::getInstance();
    }

}

