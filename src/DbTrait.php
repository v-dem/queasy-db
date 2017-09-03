<?php

namespace queasy\db;

trait DbTrait
{

    protected static function db($name = 'default')
    {
        return Db::instance($name);
    }

}

