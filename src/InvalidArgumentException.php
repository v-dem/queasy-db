<?php

/*
 * Queasy PHP Framework - Database
 *
 * (c) Vitaly Demyanenko <vitaly_demyanenko@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace queasy\db;

use InvalidArgumentException as StandardInvalidArgumentException;

/**
 * InvalidArgumentException
 */
class InvalidArgumentException extends StandardInvalidArgumentException
{
    /**
     * Create exception for not implemented QueryInterface case.
     *
     * @param string $className Loader class name
     *
     * @return InvalidArgumentException Exception instance
     */
    public static function queryInterfaceNotImplemented($className)
    {
        return new InvalidArgumentException(sprintf('Query class "%s" does not implement queasy\db\query\QueryInterface.', $queryClass));
    }

    public static function missingQueryString()
    {
        return new InvalidArgumentException('Query string is missing or not a string.');
    }

    public static function argumentNotCallable()
    {
        return new InvalidArgumentException('Argument is not callable.');
    }

    public static function rowsUnexpectedValue()
    {
        return new InvalidArgumentException('Unexpected value in rows array, must be array or object.');
    }
}

