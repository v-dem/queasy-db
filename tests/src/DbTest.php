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
        $this->pdo = new PDO('sqlite:tests/resources/test.sqlite.temp');
    }

    public function tearDown(): void
    {
        $this->pdo->exec('
            DELETE  FROM `users`');

        $this->pdo = null;
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

    public function testRunSelect()
    {
        $db = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $statement = $db->run('
            SELECT  count(*)
            FROM    `user_roles`');

        $this->assertInstanceOf('PDOStatement', $statement);

        $row = $statement->fetch();

        $this->assertEquals(3, $row[0]);
    }

    public function testInvokeSelect()
    {
        $db = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $statement = $db('
            SELECT  count(*)
            FROM    `user_roles`');

        $this->assertInstanceOf('PDOStatement', $statement);

        $row = $statement->fetch();

        $this->assertEquals(3, $row[0]);
    }

    public function testInvokeSelectWithParameters()
    {
        $db = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp'], 'fetchMode' => PDO::FETCH_ASSOC]);

        $statement = $db('
            SELECT  *
            FROM    `user_roles`
            WHERE   `id` = ?', [
                2
            ]
        );

        $this->assertInstanceOf('PDOStatement', $statement);

        $row = $statement->fetch();

        $this->assertEquals(2, $row['id']);
        $this->assertEquals('Manager', $row['name']);
    }

    public function testInvokeSelectWithNamedParameters()
    {
        $db = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp'], 'fetchMode' => PDO::FETCH_ASSOC]);

        $statement = $db('
            SELECT  *
            FROM    `user_roles`
            WHERE   `id` = :id', [
                'id' => 2
            ]
        );

        $this->assertInstanceOf('PDOStatement', $statement);

        $row = $statement->fetch();

        $this->assertEquals(2, $row['id']);
        $this->assertEquals('Manager', $row['name']);
    }

    public function testRunInsert()
    {
        $db = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $statement = $db->run('
            INSERT  INTO `users` (`id`, `email`, `password_hash`)
            VALUES  (1, \'john.doe@example.com\', \'34896830491683096\'),
                    (45, \'mary.jones@example.com\', \'9387460918340139684\')');

        $this->assertEquals(2, $statement->rowCount());
    }

    public function testRunCustomQuery()
    {
        $db = new Db([
            'connection' => [
                'path' => 'tests/resources/test.sqlite.temp'
            ],
            'queries' => [
                'selectUserRoleByName' => [
                    'sql' => '
                        SELECT  *
                        FROM    `user_roles`
                        WHERE   `name` = :name'
                ]
            ],
            'fetchMode' => PDO::FETCH_ASSOC
        ]);

        $statement = $db->selectUserRoleByName(['name' => 'Manager']);

        $row = $statement->fetch();

        $this->assertEquals(2, $row['id']);
        $this->assertEquals('Manager', $row['name']);
    }

}

