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
        $this->pdo->exec('DELETE  FROM `users`');
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

    public function testInsertNamed()
    {
        $this->db->users[] = ['id' => 15, 'email' => 'john.doe@example.com', 'password_hash' => sha1('gfhjkm')];

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

    public function testUpdateOne()
    {
        $this->db->users[] = [
            [15, 'john.doe@example.com', sha1('gfhjkm')],
            [22, 'mary.jones@example.com', sha1('321654')]
        ];

        $this->db->users->update(['email' => 'vitaly.d@example.com'], 'id', 15);

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

        $this->db->users->update(['password_hash' => sha1('secret')]);

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
}

