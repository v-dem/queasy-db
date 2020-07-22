<?php

namespace queasy\db;

use ArrayAccess;

use InvalidArgumentException;

class Connection
{
    const DEFAULT = 'sqlite::memory:';
    const DEFAULT_DRIVER = 'sqlite';

    const GENERIC_TEMPLATE = '%s:host=%s;port=%s;dbname=%s';
    const SQLITE_TEMPLATE = 'sqlite:%s';

    /**
     * @var string Connection string.
     */
    private $string;

    /**
     * Constructor.
     *
     * @param string|array|ArrayAccess $config String representing DSN, or array (or ArrayAccess instance) with database connection config options
     *
     * @throws DbException When $config doesn't represent a recognizable structure to build connection string
     */
    public function __construct($config = null)
    {
        if (empty($config)) {
            $this->string = static::DEFAULT;

            return;
        }

        if (is_string($config)) {
            $this->string = $config;

            return;
        }

        if (is_array($config) || (is_object($config) && ($config instanceof ArrayAccess))) {
            if (isset($config['dsn'])) {
                $this->string = $config['dsn'];

                return;
            }
/*
            $driver = isset($config['driver'])? $config['driver']: static::DEFAULT_DRIVER;
            switch ($driver) {
                case static::DEFAULT_DRIVER:
                    $this->string = sprintf(static::SQLITE_TEMPLATE, isset($config['path'])? $config['path']: ':memory:');

                    break;

                default:
                    $this->string = sprintf(
                        static::GENERIC_TEMPLATE,
                        $driver,
                        isset($config['host'])? $config['host']: null,
                        isset($config['port'])? $config['port']: null,
                        isset($config['name'])? $config['name']: null
                    );
            }
*/
            $this->string = isset($config['driver'])
                ? sprintf(
                    static::GENERIC_TEMPLATE,
                    $config['driver'],
                    isset($config['host'])? $config['host']: null,
                    isset($config['port'])? $config['port']: null,
                    isset($config['name'])? $config['name']: null)
                : sprintf(
                    static::SQLITE_TEMPLATE,
                    isset($config['path'])
                        ? $config['path']
                        : ':memory:');

            return;
        }

        throw new InvalidArgumentException('Wrong config argument.');
    }

    /**
     * Returns generated connection string.
     *
     * @return string Connection string
     */
    public function get()
    {
        return $this->string;
    }

    /**
     * Returns generated connection string when class instance is invoked as a function.
     *
     * @return string Connection string
     */
    public function __invoke()
    {
        return $this->get();
    }
}

