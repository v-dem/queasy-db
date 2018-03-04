<?php

namespace queasy\db;

use queasy\config\ConfigInterface;

class ConnectionString
{
    const DEFAULT = 'sqlite::memory:';
    const DEFAULT_DRIVER = 'sqlite';

    const GENERIC_TEMPLATE = '%s:host=%s;port=%s;dbname=%s';
    const SQLITE_TEMPLATE = 'sqlite:%s';

    private $string;

    public function __construct(ConfigInterface $config = null)
    {
        if (!$config) {
            $this->string = static::DEFAULT;
        } else {
            $driver = $config->get('driver', static::DEFAULT_DRIVER);
            switch ($driver) {
                case static::DEFAULT_DRIVER:
                    $this->string = sprintf(static::SQLITE_TEMPLATE, $config->get('path', ':memory:'));
                    break;

                default:
                    $this->string = sprintf(
                        static::GENERIC_TEMPLATE,
                        $driver,
                        $config->host,
                        $config->port,
                        $config->name
                    );
            }
        }
    }

    public function get()
    {
        return $this->string;
    }
}

