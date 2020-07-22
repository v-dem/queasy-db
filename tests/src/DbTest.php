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
use PDOException;

use Exception;
use InvalidArgumentException;
use BadMethodCallException;

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
        $qdb = new Db();

        $this->assertInstanceOf('PDO', $qdb);
    }

    public function testConstructorWithoutParametersAndExec()
    {
        $qdb = new Db();

        $qdb->exec('
            CREATE  TABLE `users` (
                    `id`            integer primary key,
                    `email`         text not null unique,
                    `password_hash` text not null
            )');

        $qdb->exec('
            INSERT  INTO `users` (`id`, `email`, `password_hash`)
            VALUES  (12, \'john.doe@example.com\', \'7328576391847569\')');

        $statement = $qdb->query('
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
        $qdb = new Db('sqlite:tests/resources/test.sqlite.temp');

        $this->assertCount(3, $qdb->user_roles);
    }

    public function testConstructorWithWrongDSN()
    {
        $this->expectException(PDOException::class);

        new Db('wrong dsn');
    }

    public function testConstructorWithWrongDSNNumeric()
    {
        $this->expectException(InvalidArgumentException::class);

        new Db(32167);
    }

    public function testGetTable()
    {
        $qdb = new Db();

        $table = $qdb->table('users');

        $this->assertInstanceOf(\queasy\db\Table::class, $table);
        $this->assertEquals('users', $table->name());
    }

    public function testGetTableTwice()
    {
        $qdb = new Db();

        $table = $qdb->table('users');
        $table2 = $qdb->table('users');

        $this->assertSame($table, $table2);
    }

    public function testGetTableAsProperty()
    {
        $qdb = new Db();

        $table = $qdb->users;

        $this->assertInstanceOf('queasy\db\Table', $table);
        $this->assertEquals('users', $table->name());
    }

    public function testGetTableAsArrayItem()
    {
        $qdb = new Db();

        $table = $qdb['users'];

        $this->assertInstanceOf('queasy\db\Table', $table);
        $this->assertEquals('users', $table->name());
    }

    public function testSetTableAsArrayItem()
    {
        $qdb = new Db();

        $this->expectException(BadMethodCallException::class);

        $qdb['users'] = true;
    }

    public function testIssetTableAsArrayItem()
    {
        $qdb = new Db();

        $this->expectException(BadMethodCallException::class);

        isset($qdb['users']);
    }

    public function testUnsetTableAsArrayItem()
    {
        $qdb = new Db();

        $this->expectException(BadMethodCallException::class);

        unset($qdb['users']);
    }

    public function testRunSelect()
    {
        $qdb = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $statement = $qdb->run('
            SELECT  count(*)
            FROM    `user_roles`');

        $this->assertInstanceOf('PDOStatement', $statement);

        $row = $statement->fetch();

        $this->assertEquals(3, $row[0]);
    }

    public function testInvokeSelect()
    {
        $qdb = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $statement = $qdb('
            SELECT  count(*)
            FROM    `user_roles`');

        $this->assertInstanceOf('PDOStatement', $statement);

        $row = $statement->fetch();

        $this->assertEquals(3, $row[0]);
    }

    public function testInvokeSelectWithParameters()
    {
        $qdb = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp'], 'fetchMode' => PDO::FETCH_ASSOC]);

        $statement = $qdb('
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
        $qdb = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp'], 'fetchMode' => PDO::FETCH_ASSOC]);

        $statement = $qdb('
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
        $qdb = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $statement = $qdb->run('
            INSERT  INTO `users` (`id`, `email`, `password_hash`)
            VALUES  (1, \'john.doe@example.com\', \'34896830491683096\'),
                    (45, \'mary.jones@example.com\', \'9387460918340139684\')');

        $this->assertEquals(2, $statement->rowCount());
    }

    public function testRunCustomQuery()
    {
        $qdb = new Db([
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

        $statement = $qdb->selectUserRoleByName(['name' => 'Manager']);

        $row = $statement->fetch();

        $this->assertEquals(2, $row['id']);
        $this->assertEquals('Manager', $row['name']);

        $this->assertFalse($statement->fetch());
    }

    public function testRunCustomQueryNotDeclared()
    {
        $qdb = new Db([
            'connection' => [
                'path' => 'tests/resources/test.sqlite.temp'
            ],
            'queries' => [
            ],
            'fetchMode' => PDO::FETCH_ASSOC,
        ]);

        $this->expectException(BadMethodCallException::class);

        $qdb->selectUserRoleByName(['name' => 'Manager']);
    }

    public function testId()
    {
        $qdb = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $qdb->run('
            INSERT  INTO `users` (`id`, `email`, `password_hash`)
            VALUES  (45, \'mary.jones@example.com\', \'9387460918340139684\')');

        $this->assertEquals(45, $qdb->id());
    }

    public function testTrans()
    {
        $qdb = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $qdb->trans(function() use($qdb) {
            $qdb->run('
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
        $qdb = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $this->expectException(Exception::class);

        $qdb->trans(function() use($qdb) {
            $qdb->run('
                INSERT  INTO `users` (`id`, `email`, `password_hash`)
                VALUES  (45, \'mary.jones@example.com\', \'9387460918340139684\')');

            throw new Exception();

            $qdb->run('
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
        $qdb = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp']]);

        $this->expectException(InvalidArgumentException::class);

        $qdb->trans(123);
    }
}

