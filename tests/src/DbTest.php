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

use Exception;
use InvalidArgumentException;

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
        $this->pdo->exec('DELETE FROM `users`');
        $this->pdo->exec('DELETE FROM `ids`');

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

    public function testConstructorWithPDOParameters()
    {
        $db = new Db('sqlite:tests/resources/test.sqlite.temp');

        $this->assertCount(3, $db->user_roles);
    }

    public function testConstructorWithWrongDSN()
    {
        $this->expectException(DbException::class);

        $db = new Db('wrong dsn');
    }

    public function testConstructorWithWrongDSNNumeric()
    {
        $this->expectException(DbException::class);

        $db = new Db(32167);
    }

    public function testConstructorWithStatementClass()
    {
        $db = new Db([
            'connection' => [
                'path' => 'tests/resources/test.sqlite.temp'
            ],
            'statement' => FakeStatement::class
        ]);

        $statement = $db('SELECT * FROM `user_roles`');

        $this->assertInstanceOf(FakeStatement::class, $statement);
    }

    public function testGetTable()
    {
        $db = new Db();

        $table = $db->table('users');

        $this->assertInstanceOf(\queasy\db\Table::class, $table);
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

    public function testSetTableAsArrayItem()
    {
        $db = new Db();

        $this->expectException(DbException::class);

        $db['users'] = true;
    }

    public function testIssetTableAsArrayItem()
    {
        $db = new Db();

        $this->expectException(DbException::class);

        isset($db['users']);
    }

    public function testUnsetTableAsArrayItem()
    {
        $db = new Db();

        $this->expectException(DbException::class);

        unset($db['users']);
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
            'fetchMode' => PDO::FETCH_ASSOC,
        ]);

        $statement = $db->selectUserRoleByName(['name' => 'Manager']);

        $row = $statement->fetch();

        $this->assertEquals(2, $row['id']);
        $this->assertEquals('Manager', $row['name']);

        $this->assertFalse($statement->fetch());
    }

    public function testRunCustomQueryNotDeclared()
    {
        $db = new Db([
            'connection' => [
                'path' => 'tests/resources/test.sqlite.temp'
            ],
            'queries' => [
            ],
            'fetchMode' => PDO::FETCH_ASSOC,
        ]);

        $this->expectException(DbException::class);

        $statement = $db->selectUserRoleByName(['name' => 'Manager']);
    }

    public function testId()
    {
        $db = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $db->run('
            INSERT  INTO `users` (`id`, `email`, `password_hash`)
            VALUES  (45, \'mary.jones@example.com\', \'9387460918340139684\')');

        $this->assertEquals(45, $db->id());
    }

    public function testTrans()
    {
        $db = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $db->trans(function() use($db) {
            $db->run('
                INSERT  INTO `users` (`id`, `email`, `password_hash`)
                VALUES  (45, \'mary.jones@example.com\', \'9387460918340139684\')');
        });

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 45')->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($row);
        $this->assertEquals(45, $row['id']);
        $this->assertEquals('mary.jones@example.com', $row['email']);
    }

    public function testTransFailed()
    {
        $db = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $this->expectException(Exception::class);

        $db->trans(function() use($db) {
            $db->run('
                INSERT  INTO `users` (`id`, `email`, `password_hash`)
                VALUES  (45, \'mary.jones@example.com\', \'9387460918340139684\')');

            throw new Exception();

            $db->run('
                INSERT  INTO `users` (`id`, `email`, `password_hash`)
                VALUES  (7, \'john.doe@example.com\', \'124284\')');
        });

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 7')->fetch(PDO::FETCH_ASSOC);
        $this->assertNull($row);

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 45')->fetch(PDO::FETCH_ASSOC);
        $this->assertNull($row);
    }

    public function testTransNotCallable()
    {
        $db = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $this->expectException(InvalidArgumentException::class);

        $db->trans(123);
    }
}

