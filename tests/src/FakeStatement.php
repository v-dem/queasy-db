<?php

/*
 * Queasy PHP Framework - Database - Tests
 *
 * (c) Vitaly Demyanenko <vitaly_demyanenko@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace queasy\db\tests;

use PDO;
use PDOStatement;

/**
 * @codeCoverageIgnore
 */
class FakeStatement extends PDOStatement
{
    protected $pdo;

    protected function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}

