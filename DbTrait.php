<?php

namespace queasy\db;

trait DbTrait
{

    private static function db($name = 'default')
    {
        return Db::getInstance($name);
    }

}

