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

class TableTest extends TestCase
{
    private $db;

    private $pdo;

    public function setUp(): void
    {
        $this->db = new Db(['connection' => ['path' => 'tests/resources/test.sqlite.temp'], 'fetchMode' => Db::FETCH_ASSOC]);

        $this->pdo = new PDO('sqlite:tests/resources/test.sqlite.temp');
    }

    public function tearDown(): void
    {
        $this->pdo->exec('DELETE FROM `users`');
        $this->pdo->exec('DELETE FROM `ids`');

        $this->pdo = null;
    }

    public function testInsert()
    {
        $this->db->users[] = [15, 'john.doe@example.com', sha1('gfhjkm')];

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 15')->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($row);
        $this->assertEquals(15, $row['id']);
        $this->assertEquals('john.doe@example.com', $row['email']);
        $this->assertEquals(sha1('gfhjkm'), $row['password_hash']);
    }

    public function testFunctionInsert()
    {
        $id = $this->db->users->insert([15, 'john.doe@example.com', sha1('gfhjkm')]);
        $this->assertEquals(15, $id);

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 15')->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($row);
        $this->assertEquals(15, $row['id']);
        $this->assertEquals('john.doe@example.com', $row['email']);
        $this->assertEquals(sha1('gfhjkm'), $row['password_hash']);
    }

    public function testInsertNamed()
    {
        $this->db->users[] = ['id' => 15, 'email' => 'john.doe@example.com', 'password_hash' => sha1('gfhjkm')];

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 15')->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($user);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);
    }

    public function testFunctionInsertNamed()
    {
        $id = $this->db->users->insert(['id' => 15, 'email' => 'john.doe@example.com', 'password_hash' => sha1('gfhjkm')]);
        $this->assertEquals(15, $id);

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 15')->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($user);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);
    }

    public function testBatchInsert()
    {
        $this->db->users[] = [
            [15, 'john.doe@example.com', sha1('gfhjkm')],
            [22, 'mary.jones@example.com', sha1('321654')]
        ];

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 15')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($user);
        $this->assertEquals(15, $user['id']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 22')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($user);
        $this->assertEquals(22, $user['id']);
        $this->assertEquals('mary.jones@example.com', $user['email']);
        $this->assertEquals(sha1('321654'), $user['password_hash']);
    }

    public function testFunctionBatchInsert()
    {
        $rowsCount = $this->db->users->insert([
            [15, 'john.doe@example.com', sha1('gfhjkm')],
            [22, 'mary.jones@example.com', sha1('321654')]
        ]);
        $this->assertEquals(2, $rowsCount);

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 15')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($user);
        $this->assertEquals(15, $user['id']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 22')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($user);
        $this->assertEquals(22, $user['id']);
        $this->assertEquals('mary.jones@example.com', $user['email']);
        $this->assertEquals(sha1('321654'), $user['password_hash']);
    }

    public function testBatchInsertNamed()
    {
        $this->db->users[] = [
            ['id', 'email', 'password_hash'],
            [
                [15, 'john.doe@example.com', sha1('gfhjkm')],
                [22, 'mary.jones@example.com', sha1('321654')]
            ]
        ];

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 15')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($user);
        $this->assertEquals(15, $user['id']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 22')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($user);
        $this->assertEquals(22, $user['id']);
        $this->assertEquals('mary.jones@example.com', $user['email']);
        $this->assertEquals(sha1('321654'), $user['password_hash']);
    }

    public function testBatchInsertSeparatelyNamed()
    {
        $this->db->users[] = [
            ['id' => 15, 'email' => 'john.doe@example.com', 'password_hash' => sha1('gfhjkm')],
            ['id' => 22, 'email' => 'mary.jones@example.com', 'password_hash' => sha1('321654')]
        ];

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 15')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($user);
        $this->assertEquals(15, $user['id']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 22')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($user);
        $this->assertEquals(22, $user['id']);
        $this->assertEquals('mary.jones@example.com', $user['email']);
        $this->assertEquals(sha1('321654'), $user['password_hash']);
    }

    public function testInsertEmpty()
    {
        $this->db->ids[] = [];

        $row = $this->pdo->query('SELECT count(*) FROM `ids`')->fetch(PDO::FETCH_NUM);
        $this->assertEquals(1, $row[0]);
    }

    // Such usage can't be implemented without very dirty tricks
    /*
    public function testInsertTwoEmpty()
    {
        $this->db->ids[] = [[], []];

        $row = $this->pdo->query('SELECT count(*) FROM `ids`')->fetch(PDO::FETCH_NUM);
        $this->assertEquals(2, $row[0]);
    }
    */

    public function testFunctionInsertEmpty()
    {
        $id = $this->db->ids->insert();

        $this->assertTrue(is_numeric($id));

        $id = $this->pdo->query('SELECT * FROM `ids` WHERE `id` = ' . $id)->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($id);
    }

    public function testUpdateOne()
    {
        $this->db->users[] = [
            [15, 'john.doe@example.com', sha1('gfhjkm')],
            [22, 'mary.jones@example.com', sha1('321654')]
        ];

        $rowsCount = $this->db->users->update(['email' => 'vitaly.d@example.com'], 'id', 15);
        $this->assertEquals(1, $rowsCount);

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 15')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($user);
        $this->assertEquals(15, $user['id']);
        $this->assertEquals('vitaly.d@example.com', $user['email']);
        $this->assertEquals(sha1('gfhjkm'), $user['password_hash']);

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 22')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($user);
        $this->assertEquals(22, $user['id']);
        $this->assertEquals('mary.jones@example.com', $user['email']);
        $this->assertEquals(sha1('321654'), $user['password_hash']);
    }

    public function testUpdateAll()
    {
        $this->db->users[] = [
            [15, 'john.doe@example.com', sha1('gfhjkm')],
            [22, 'mary.jones@example.com', sha1('321654')]
        ];

        $rowsCount = $this->db->users->update(['password_hash' => sha1('secret')]);
        $this->assertEquals(2, $rowsCount);

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 15')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($user);
        $this->assertEquals(15, $user['id']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals(sha1('secret'), $user['password_hash']);

        $user = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 22')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($user);
        $this->assertEquals(22, $user['id']);
        $this->assertEquals('mary.jones@example.com', $user['email']);
        $this->assertEquals(sha1('secret'), $user['password_hash']);
    }

    public function testCount()
    {
        $this->assertEquals(3, count($this->db->user_roles));
    }

    public function testForeach()
    {
        $this->pdo->exec('
            INSERT  INTO `users`
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $expectedIds = [7, 12, 123];
        $expectedEmails = ['john.doe@example.com', 'mary.jones@example.com', 'vitaly.d@example.com'];
        $expectedPasswordHashes = ['7346598173659873', '2341341421', '75647454'];
        $rowsCount = 0;
        foreach ($this->db->users as $user) {
            $offset = array_search($user['id'], $expectedIds);
            $this->assertTrue(is_numeric($offset));
            $this->assertEquals($expectedIds[$offset], $user['id']);
            $this->assertEquals($expectedEmails[$offset], $user['email']);
            $this->assertEquals($expectedPasswordHashes[$offset], $user['password_hash']);
            $rowsCount++;
        }
        $this->assertEquals(3, $rowsCount);
    }
}

