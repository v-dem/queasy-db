<?php

namespace queasy\db;

use Exception;

class DbException extends Exception
{
    public static function connectionFailed($e = null)
    {
        return new DbException('Cannot connect to database.', 0, $e);
    }

    public static function statementClassNotSet()
    {
        return new DbException('Cannot set statement class.');
    }

    public static function errorModeNotSet()
    {
        return new DbException('Cannot set error mode to exceptions.');
    }

    public static function fetchModeNotSet()
    {
        return new DbException('Cannot set default fetch mode.');
    }

    public static function queryNotDeclared($name)
    {
        return new DbException(sprintf('Query named "%s" was not declared in configuration.', $name));
    }

    public static function tableMethodNotImplemented($table, $method)
    {
        return new DbException(sprintf('Method "%s" not implemented for table "%s".', $method, $table));
    }

    public static function cannotPrepareStatement($query, \Exception $e)
    {
        return new DbException(sprintf('Cannot prepare statement: %s', $query), null, $e);
    }

    public static function cannotExecuteQuery($query, $errorCode, $errorMessage)
    {
        return new DbException(sprintf('Can\'t execute query (%s: %s): %s', $errorCode, $errorMessage, $query));
    }

    public static function noValueSelected($query)
    {
        return new DbException(sprintf('No value was selected by query: %s', $query));
    }
}

