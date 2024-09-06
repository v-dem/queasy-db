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

use BadMethodCallException;

use queasy\db\Db;
use queasy\db\DbException;

class TableTest extends TestCase
{
    private $qdb;

    private $pdo;

    public function setUp(): void
    {
        $this->qdb = new Db([
            'connection' => [
                'dsn' => 'sqlite:tests/resources/test.sqlite.temp'
            ],
            'tables' => [
                'users' => [
                    'deleteWithSubstringInEmail' => [
                        'sql' => '
                            DELETE  FROM "users"
                            WHERE   "email" LIKE (\'%\' || :substring || \'%\')'
                    ],
                    'selectWithSubstringInEmailBackOrdered' => [
                        'sql' => '
                            SELECT  *
                            FROM    "users"
                            WHERE   "email" LIKE (\'%\' || :substring || \'%\')
                            ORDER   BY "id" DESC',
                        'returns' => Db::RETURN_ALL
                    ],
                    'getLatestById' => [
                        'sql' => '
                            SELECT  *
                            FROM    "users"
                            ORDER   BY "id" DESC
                            LIMIT   1',
                        'returns' => Db::RETURN_ONE
                    ]
                ],
                'user_roles' => [
                    'getRolesCount' => [
                        'sql' => '
                            SELECT  count(*)
                            FROM    "user_roles"',
                        'returns' => Db::RETURN_VALUE
                    ]
                ]
            ]
        ]);

        $this->pdo = new PDO('sqlite:tests/resources/test.sqlite.temp');
    }

    public function tearDown(): void
    {
        $this->pdo->exec('DELETE FROM "users"');
        $this->pdo->exec('DELETE FROM "ids"');

        $this->pdo = null;
    }

    public function testInsert()
    {
        $this->qdb->users[] = [15, 'john.doe@example.com', sha1('gfhjkm')];

        $row = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 15')->fetch();

        $this->assertNotNull($row);
        $this->assertEquals(15, $row['id']);
        $this->assertEquals('john.doe@example.com', $row['email']);
        $this->assertEquals(sha1('gfhjkm'), $row['password_hash']);
    }

    public function testFunctionInsert()
    {
        $userId = $this->qdb->users->insert([15, 'john.doe@example.com', sha1('gfhjkm')]);
        $this->assertEquals(15, $userId);

        $row = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 15')->fetch();

        $this->assertNotNull($row);
        $this->assertEquals(15, $row['id']);
        $this->assertEquals('john.doe@example.com', $row['email']);
        $this->assertEquals(sha1('gfhjkm'), $row['password_hash']);
    }

    public function testInsertNamed()
    {
        $this->qdb->users[] = ['id' => 15, 'email' => 'john.doe@example.com', 'password_hash' => sha1('gfhjkm')];

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 15')->fetch();

        $this->assertNotNull($user);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);
    }

    public function testFunctionInsertNamed()
    {
        $userId = $this->qdb->users->insert(['id' => 15, 'email' => 'john.doe@example.com', 'password_hash' => sha1('gfhjkm')]);
        $this->assertEquals(15, $userId);

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 15')->fetch();

        $this->assertNotNull($user);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);
    }

    public function testBatchInsert()
    {
        $this->qdb->users[] = [
            [15, 'john.doe@example.com', sha1('gfhjkm')],
            [22, 'mary.jones@example.com', sha1('321654')]
        ];

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 15')->fetch();
        $this->assertNotNull($user);
        $this->assertEquals(15, $user['id']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 22')->fetch();
        $this->assertNotNull($user);
        $this->assertEquals(22, $user['id']);
        $this->assertEquals('mary.jones@example.com', $user['email']);
        $this->assertEquals(sha1('321654'), $user['password_hash']);
    }

    public function testFunctionBatchInsert()
    {
        $rowsCount = $this->qdb->users->insert([
            [15, 'john.doe@example.com', sha1('gfhjkm')],
            [22, 'mary.jones@example.com', sha1('321654')]
        ]);
        $this->assertEquals(2, $rowsCount);

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 15')->fetch();
        $this->assertNotNull($user);
        $this->assertEquals(15, $user['id']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 22')->fetch();
        $this->assertNotNull($user);
        $this->assertEquals(22, $user['id']);
        $this->assertEquals('mary.jones@example.com', $user['email']);
        $this->assertEquals(sha1('321654'), $user['password_hash']);
    }

    public function testBatchInsertNamed()
    {
        $this->qdb->users[] = [
            ['id', 'email', 'password_hash'],
            [
                [15, 'john.doe@example.com', sha1('gfhjkm')],
                [22, 'mary.jones@example.com', sha1('321654')]
            ]
        ];

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 15')->fetch();
        $this->assertNotNull($user);
        $this->assertEquals(15, $user['id']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 22')->fetch();
        $this->assertNotNull($user);
        $this->assertEquals(22, $user['id']);
        $this->assertEquals('mary.jones@example.com', $user['email']);
        $this->assertEquals(sha1('321654'), $user['password_hash']);
    }

    public function testBatchInsertSeparatelyNamed()
    {
        $this->qdb->users[] = [
            ['id' => 15, 'email' => 'john.doe@example.com', 'password_hash' => sha1('gfhjkm')],
            ['id' => 22, 'email' => 'mary.jones@example.com', 'password_hash' => sha1('321654')]
        ];

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 15')->fetch();
        $this->assertNotNull($user);
        $this->assertEquals(15, $user['id']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 22')->fetch();
        $this->assertNotNull($user);
        $this->assertEquals(22, $user['id']);
        $this->assertEquals('mary.jones@example.com', $user['email']);
        $this->assertEquals(sha1('321654'), $user['password_hash']);
    }

    public function testInsertEmpty()
    {
        $this->qdb->ids[] = [];

        $row = $this->pdo->query('SELECT count(*) FROM "ids"')->fetch(PDO::FETCH_NUM);
        $this->assertEquals(1, $row[0]);
    }

    public function testInsertByOffset()
    {
        $this->expectException(BadMethodCallException::class);

        $this->qdb->ids[12] = [22];
    }

    // Such usage can't be implemented without very dirty tricks
    /*
    public function testInsertTwoEmpty()
    {
        $this->qdb->ids[] = [[], []];

        $row = $this->pdo->query('SELECT count(*) FROM "ids"')->fetch(PDO::FETCH_NUM);
        $this->assertEquals(2, $row[0]);
    }
    */

    public function testFunctionInsertEmpty()
    {
        $uniqueId = $this->qdb->ids->insert();

        $this->assertIsNumeric($uniqueId);

        $row = $this->pdo->query('SELECT * FROM "ids" WHERE "id" = ' . $uniqueId)->fetch();

        $this->assertNotNull($uniqueId);
        $this->assertEquals($uniqueId, $row['id']);
    }

    public function testUpdateOne()
    {
        $this->qdb->users[] = [
            [15, 'john.doe@example.com', sha1('gfhjkm')],
            [22, 'mary.jones@example.com', sha1('321654')]
        ];

        $rowsCount = $this->qdb->users->update(['email' => 'vitaly.d@example.com'], 'id', 15);
        $this->assertEquals(1, $rowsCount);

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 15')->fetch();
        $this->assertNotNull($user);
        $this->assertEquals(15, $user['id']);
        $this->assertEquals('vitaly.d@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 22')->fetch();
        $this->assertNotNull($user);
        $this->assertEquals(22, $user['id']);
        $this->assertEquals('mary.jones@example.com', $user['email']);
        $this->assertEquals(sha1('321654'), $user['password_hash']);
    }

    public function testUpdateAll()
    {
        $this->qdb->users[] = [
            [15, 'john.doe@example.com', sha1('gfhjkm')],
            [22, 'mary.jones@example.com', sha1('321654')]
        ];

        $rowsCount = $this->qdb->users->update(['password_hash' => sha1('secret')]);
        $this->assertEquals(2, $rowsCount);

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 15')->fetch();
        $this->assertNotNull($user);
        $this->assertEquals(15, $user['id']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('secret'), $user['password_hash']);

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 22')->fetch();
        $this->assertNotNull($user);
        $this->assertEquals(22, $user['id']);
        $this->assertEquals('mary.jones@example.com', $user['email']);
        $this->assertEquals(sha1('secret'), $user['password_hash']);
    }

    public function testCount()
    {
        $this->assertCount(3, $this->qdb->user_roles);
    }

    public function testForeach()
    {
        $this->pdo->exec('
            INSERT  INTO "users"
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $expectedIds = [7, 12, 123];
        $expectedEmails = ['john.doe@example.com', 'mary.jones@example.com', 'vitaly.d@example.com'];
        $expectedPasswordHashes = ['7346598173659873', '2341341421', '75647454'];
        $rowsCount = 0;
        foreach ($this->qdb->users as $user) {
            $offset = array_search($user['id'], $expectedIds);
            $this->assertIsNumeric($offset);
            $this->assertEquals($expectedIds[$offset], $user['id']);
            $this->assertEquals($expectedEmails[$offset], $user['email']);
            $this->assertEquals($expectedPasswordHashes[$offset], $user['password_hash']);
            $rowsCount++;
        }

        $this->assertEquals(3, $rowsCount);
    }

    public function testAll()
    {
        $userRoles = $this->qdb->user_roles->all();

        $this->assertCount(3, $userRoles);
    }

    public function testCustomRemoveMethod()
    {
        $this->pdo->exec('
            INSERT  INTO "users"
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $this->qdb->users->deleteWithSubstringInEmail(['substring' => 'jo']);

        $user = $this->pdo->query('SELECT * FROM "users" WHERE "id" = 123')->fetch();
        $this->assertNotNull($user);

        $row = $this->pdo->query('SELECT count(*) FROM "users"')->fetch(PDO::FETCH_NUM);
        $this->assertEquals(1, $row[0]);
    }

    public function testCustomSelectMethodReturningAll()
    {
        $this->pdo->exec('
            INSERT  INTO "users"
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $users = $this->qdb->users->selectWithSubstringInEmailBackOrdered(['substring' => 'jo']);
        $this->assertCount(2, $users);

        $this->assertEquals(12, $users[0]['id']);
        $this->assertEquals('mary.jones@example.com', $users[0]['email']);
        $this->assertEquals('2341341421', $users[0]['password_hash']);

        $this->assertEquals(7, $users[1]['id']);
        $this->assertEquals('john.doe@example.com', $users[1]['email']);
        $this->assertEquals('7346598173659873', $users[1]['password_hash']);
    }

    public function testCustomSelectMethodReturningOne()
    {
        $this->pdo->exec('
            INSERT  INTO "users"
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $user = $this->qdb->users->getLatestById();

        $this->assertNotNull($user);

        $this->assertEquals(123, $user['id']);
        $this->assertEquals('vitaly.d@example.com', $user['email']);
        $this->assertEquals('75647454', $user['password_hash']);
    }

    public function testCustomSelectMethodReturningValue()
    {
        $this->pdo->exec('
            INSERT  INTO "users"
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $rolesCount = $this->qdb->user_roles->getRolesCount();

        $this->assertEquals(3, $rolesCount);
    }
}

