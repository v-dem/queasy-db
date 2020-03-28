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
        $this->pdo->exec('
            DELETE  FROM `users`');
    }

    public function testInsert()
    {
        $this->db->users[] = [15, 'john.doe@example.com', sha1('gfhjkm')];

        $statement = $this->pdo->query('
            SELECT  *
            FROM    `users`
            WHERE   `id` = 15');
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($row);
        $this->assertEquals(15, $row['id']);
        $this->assertEquals('john.doe@example.com', $row['email']);
        $this->assertEquals(sha1('gfhjkm'), $row['password_hash']);
    }
/*
    public function testInsertNamed()
    {
        $this->db->users[] = ['id' => 15, 'name' => 'john', 'email' => 'john.doe@example.com'];

        $user = $this->db->users->id[15];

        $this->assertEquals('john', $user['name']);
    }

    public function testBatchInsert()
    {
        $this->db->users[] = [
            [15, 'john', 'john.doe@example.com'],
            [22, 'mary', 'mary.doe@example.com']
        ];

        $user = $this->db->users->id[15];
        $this->assertEquals('john', $user['name']);

        $user = $this->db->users->id[22];
        $this->assertEquals('mary', $user['name']);
    }

    public function testBatchInsertNamed()
    {
        $this->db->users[] = [
            ['id', 'name', 'email'],
            [
                [15, 'john', 'john.doe@example.com'],
                [22, 'mary', 'mary.doe@example.com']
            ]
        ];

        $user = $this->db->users->id[15];
        $this->assertEquals('john', $user['name']);

        $user = $this->db->users->id[22];
        $this->assertEquals('mary', $user['name']);
    }

    public function testBatchInsertSeparatelyNamed()
    {
        $this->db->users[] = [
            ['id' => 15, 'name' => 'john', 'email' => 'john.doe@example.com'],
            ['id' => 22, 'name' => 'mary', 'email' => 'mary.doe@example.com']
        ];

        $user = $this->db->users->id[15];
        $this->assertEquals('john', $user['name']);

        $user = $this->db->users->id[22];
        $this->assertEquals('mary', $user['name']);
    }

    public function testUpdateOne()
    {
        $this->db->users[] = [
            [15, 'john', 'john.doe@example.com'],
            [22, 'mary', 'mary.doe@example.com']
        ];

        $this->db->users->update(['name' => 'vitaly'], ['id' => 15]);

        $user = $this->db->users->id[15];
        $this->assertEquals('vitaly', $user['name']);

        $user = $this->db->users->id[22];
        $this->assertEquals('mary', $user['name']);
    }

    public function testCount()
    {
        $this->db->users[] = [
            [15, 'john', 'john.doe@example.com'],
            [22, 'mary', 'mary.doe@example.com']
        ];

        $this->assertCount(2, $this->db->users);
    }
*/
}

