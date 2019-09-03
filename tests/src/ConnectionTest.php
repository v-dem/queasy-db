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

use queasy\db\Connection;
use queasy\db\DbException;

class ConnectionTest extends TestCase
{
    public function testDefault()
    {
        $connection = new Connection();

        $this->assertEquals('sqlite::memory:', $connection());
    }

    public function testDefaultGet()
    {
        $connection = new Connection();

        $this->assertEquals('sqlite::memory:', $connection->get());
    }

    public function testMysql()
    {
        $connection = new Connection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => '9987',
            'name' => 'test'
        ]);

        $this->assertEquals('mysql:host=localhost;port=9987;dbname=test', $connection());
    }

    public function testMysqlGet()
    {
        $connection = new Connection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => '9987',
            'name' => 'test'
        ]);

        $this->assertEquals('mysql:host=localhost;port=9987;dbname=test', $connection->get());
    }

    public function testCustomDsn()
    {
        $connection = new Connection('Custom');

        $this->assertEquals('Custom', $connection());
    }

    public function testCustomDsnGet()
    {
        $connection = new Connection('Custom');

        $this->assertEquals('Custom', $connection->get());
    }

    public function testInvalidDsn()
    {
        $this->expectException(DbException::class);

        new Connection(32167);
    }

    public function testCustomDsnOption()
    {
        $connection = new Connection(['dsn' => 'Custom']);

        $this->assertEquals('Custom', $connection());
    }

    public function testCustomDsnOptionGet()
    {
        $connection = new Connection(['dsn' => 'Custom']);

        $this->assertEquals('Custom', $connection->get());
    }

    public function testInvalidDsnOption() // TODO:
    {
        $this->expectException(DbException::class);

        new Connection(32167);
    }
}

