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

use InvalidArgumentException;

use queasy\db\Connection;

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

    public function testDefaultWithPath()
    {
        $connection = new Connection(['path' => '../resources/test.sqlite.temp']);

        $this->assertEquals('sqlite:../resources/test.sqlite.temp', $connection());
    }

    public function testMysql()
    {
        $connection = new Connection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => '9987',
            'name' => 'test'
        ]);

        $this->assertEquals('mysql:host=localhost;port=9987;dbname=test;charset=utf8', $connection());
    }

    public function testMysqlGet()
    {
        $connection = new Connection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => '9987',
            'name' => 'test'
        ]);

        $this->assertEquals('mysql:host=localhost;port=9987;dbname=test;charset=utf8', $connection->get());
    }

    public function testCharset()
    {
        $connection = new Connection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => '9987',
            'name' => 'test',
            'charset' => 'cp1251'
        ]);

        $this->assertEquals('mysql:host=localhost;port=9987;dbname=test;charset=cp1251', $connection->get());
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
        $this->expectException(InvalidArgumentException::class);

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
}

