<?php

/**
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

class FieldTest extends TestCase
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

    public function testGetRecord()
    {
        $role = $this->db->user_roles->id[2];

        $this->assertEquals('Manager', $role['name']);
    }

    public function testGetRecords()
    {
        $roles = $this->db->user_roles->id[[2, 3]];

        $this->assertCount(2, $roles);

        $this->assertEquals('Manager', $roles[0]['name']);
        $this->assertEquals('User', $roles[1]['name']);
    }

    public function testDeleteAssignNull()
    {
        $this->pdo->exec('INSERT INTO `users` VALUES (7, \'john.doe@example.com\', \'7346598173659873\')');

        $row = $this->pdo->query('SELECT count(*) FROM `users`')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, array_shift($row));

        $this->db->users->id[7] = null;

        $row = $this->pdo->query('SELECT count(*) FROM `users`')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(0, array_shift($row));
    }

    public function testDeleteUnset()
    {
        $this->pdo->exec('INSERT INTO `users` VALUES (7, \'john.doe@example.com\', \'7346598173659873\')');

        unset($this->db->users->id[7]);

        $row = $this->pdo->query('SELECT count(*) FROM `users`')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(0, array_shift($row));
    }

    public function testDeleteSomeAssignNull()
    {
        $this->pdo->exec('
            INSERT  INTO `users`
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $this->db->users->id[[7, 123]] = null;

        $rows = $this->pdo->query('SELECT * FROM `users`')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(1, $rows);
        $this->assertEquals(12, $rows[0]['id']);
        $this->assertEquals('mary.jones@example.com', $rows[0]['email']);
        $this->assertEquals('2341341421', $rows[0]['password_hash']);
    }

    public function testDeleteSomeUnset()
    {
        $this->pdo->exec('
            INSERT  INTO `users`
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        unset($this->db->users->id[[7, 123]]);

        $rows = $this->pdo->query('SELECT * FROM `users`')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(1, $rows);
        $this->assertEquals(12, $rows[0]['id']);
        $this->assertEquals('mary.jones@example.com', $rows[0]['email']);
        $this->assertEquals('2341341421', $rows[0]['password_hash']);
    }
}

