<?php

/*
 * Queasy PHP Framework - Database - Tests
 *
 * (c) Vitaly Demyanenko <vitaly_demyanenko@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace queasy\db\tests;

use PHPUnit\Framework\TestCase;

use queasy\db\ConnectionString;
use queasy\db\DbException;

class ConnectionStringTest extends TestCase
{
    public function testDefault()
    {
        $connectionString = new ConnectionString();

        $this->assertEquals('sqlite::memory:', $connectionString());
    }

    public function testMysql()
    {
        $connectionString = new ConnectionString([
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => '9987',
            'name' => 'test'
        ]);

        $this->assertEquals('mysql:host=localhost;port=9987;dbname=test', $connectionString());
    }

    public function testMysqlGet()
    {
        $connectionString = new ConnectionString([
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => '9987',
            'name' => 'test'
        ]);

        $this->assertEquals('mysql:host=localhost;port=9987;dbname=test', $connectionString->get());
    }

    public function testInvalid()
    {
        $this->expectException(DbException::class);

        new ConnectionString(32167);
    }

    public function testCustomString()
    {
        $connectionString = new ConnectionString('Custom');

        $this->assertEquals('Custom', $connectionString());
    }

    public function testCustomStringGet()
    {
        $connectionString = new ConnectionString('Custom');

        $this->assertEquals('Custom', $connectionString->get());
    }
}

