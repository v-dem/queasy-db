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

use PDO;

use queasy\db\Db;
use queasy\db\DbException;

class DbTest extends TestCase
{
    private $pdo;

    public function setUp(): void
    {
        // $this->db = new Db(['connection' => ['path' => 'tests/resources/test.sqlite'], 'fetchMode' => Db::FETCH_ASSOC]);

        $this->pdo = new PDO('sqlite:tests/resources/test.sqlite');
    }

    public function tearDown(): void
    {
        $this->pdo->exec('
            DELETE  FROM `users`');
    }

    public function testConstructorWithoutParameters()
    {
        $db = new Db();

        $this->assertInstanceOf('PDO', $db);
    }

    public function testConstructorWithoutParametersAndExec()
    {
        $db = new Db();

        $db->exec('
            CREATE  TABLE `users` (
                    `id`            integer primary key,
                    `email`         text not null unique,
                    `password_hash` text not null
            )');

        $db->exec('
            INSERT  INTO `users` (`id`, `email`, `password_hash`)
            VALUES  (12, \'john.doe@example.com\', \'7328576391847569\')');

        $statement = $db->query('
            SELECT  *
            FROM    `users`');

        $this->assertInstanceOf('PDOStatement', $statement);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(1, $rows);

        $row = array_shift($rows);

        $this->assertEquals(12, $row['id']);
        $this->assertEquals('john.doe@example.com', $row['email']);
        $this->assertEquals('7328576391847569', $row['password_hash']);
    }

    public function testGetTable()
    {
        $db = new Db();

        $table = $db->table('users');

        $this->assertInstanceOf('queasy\db\Table', $table);
        $this->assertEquals('users', $table->name());
    }

    public function testGetTableTwice()
    {
        $db = new Db();

        $table = $db->table('users');
        $table2 = $db->table('users');

        $this->assertSame($table, $table2);
    }

    public function testGetTableAsProperty()
    {
        $db = new Db();

        $table = $db->users;

        $this->assertInstanceOf('queasy\db\Table', $table);
        $this->assertEquals('users', $table->name());
    }

    public function testGetTableAsArrayItem()
    {
        $db = new Db();

        $table = $db['users'];

        $this->assertInstanceOf('queasy\db\Table', $table);
        $this->assertEquals('users', $table->name());
    }
/*
    public function testRun()
    {
        $db = new Db(['connection' => ['path' => 'tests/resources/test.sqlite']]);

        $statement = $db->run('
            SELECT  count(*)
            FROM    `users`');

        $row = $statement->fetch();

        $this->assertEquals
    }
*/
}

