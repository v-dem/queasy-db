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
        $this->pdo->exec('DELETE FROM `users`');
        $this->pdo->exec('DELETE FROM `ids`');

        $this->pdo = null;
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

    public function testGetRecordInvoke()
    {
        $roles = $this->db->user_roles->id(2);

        $this->assertTrue(is_array($roles));
        $this->assertEquals('Manager', $roles[0]['name']);
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

    public function testDeleteFunction()
    {
        $this->pdo->exec('INSERT INTO `users` VALUES (7, \'john.doe@example.com\', \'7346598173659873\')');

        $row = $this->pdo->query('SELECT count(*) FROM `users`')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, array_shift($row));

        $rowsCount = $this->db->users->id->delete(7);
        $this->assertEquals(1, $rowsCount);

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

    public function testDeleteSomeFunction()
    {
        $this->pdo->exec('
            INSERT  INTO `users`
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $rowsCount = $this->db->users->id->delete([7, 123]);
        $this->assertEquals(2, $rowsCount);

        $rows = $this->pdo->query('SELECT * FROM `users`')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(1, $rows);
        $this->assertEquals(12, $rows[0]['id']);
        $this->assertEquals('mary.jones@example.com', $rows[0]['email']);
        $this->assertEquals('2341341421', $rows[0]['password_hash']);
    }

    public function testDeleteSomeFunctionWithNotExistentRecord()
    {
        $this->pdo->exec('
            INSERT  INTO `users`
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $rowsCount = $this->db->users->id->delete([7, 123, 256]);
        $this->assertEquals(2, $rowsCount);
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

    public function testUpdate()
    {
        $this->pdo->exec('
            INSERT  INTO `users`
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $this->db->users->id[7] = ['password_hash' => 'cbKBLVIWVW'];

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 7')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(7, $row['id']);
        $this->assertEquals('john.doe@example.com', $row['email']);
        $this->assertEquals('cbKBLVIWVW', $row['password_hash']);

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 123')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(123, $row['id']);
        $this->assertEquals('vitaly.d@example.com', $row['email']);
        $this->assertEquals('75647454', $row['password_hash']);
    }

    public function testUpdateFunction()
    {
        $this->pdo->exec('
            INSERT  INTO `users`
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $rowsCount = $this->db->users->id->update(7, ['password_hash' => 'cbKBLVIWVW']);
        $this->assertEquals(1, $rowsCount);

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 7')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(7, $row['id']);
        $this->assertEquals('john.doe@example.com', $row['email']);
        $this->assertEquals('cbKBLVIWVW', $row['password_hash']);

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 123')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(123, $row['id']);
        $this->assertEquals('vitaly.d@example.com', $row['email']);
        $this->assertEquals('75647454', $row['password_hash']);
    }

    public function testUpdateSome()
    {
        $this->pdo->exec('
            INSERT  INTO `users`
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $this->db->users->id[[7, 123]] = ['password_hash' => 'cbKBLVIWVW'];

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 7')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(7, $row['id']);
        $this->assertEquals('john.doe@example.com', $row['email']);
        $this->assertEquals('cbKBLVIWVW', $row['password_hash']);

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 123')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(123, $row['id']);
        $this->assertEquals('vitaly.d@example.com', $row['email']);
        $this->assertEquals('cbKBLVIWVW', $row['password_hash']);

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 12')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(12, $row['id']);
        $this->assertEquals('mary.jones@example.com', $row['email']);
        $this->assertEquals('2341341421', $row['password_hash']);
    }

    public function testUpdateFunctionSome()
    {
        $this->pdo->exec('
            INSERT  INTO `users`
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $rowsCount = $this->db->users->id->update([7, 123], ['password_hash' => 'cbKBLVIWVW']);
        $this->assertEquals(2, $rowsCount);

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 7')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(7, $row['id']);
        $this->assertEquals('john.doe@example.com', $row['email']);
        $this->assertEquals('cbKBLVIWVW', $row['password_hash']);

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 123')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(123, $row['id']);
        $this->assertEquals('vitaly.d@example.com', $row['email']);
        $this->assertEquals('cbKBLVIWVW', $row['password_hash']);

        $row = $this->pdo->query('SELECT * FROM `users` WHERE `id` = 12')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(12, $row['id']);
        $this->assertEquals('mary.jones@example.com', $row['email']);
        $this->assertEquals('2341341421', $row['password_hash']);
    }

    public function testUpdateFunctionSomeNotExistent()
    {
        $this->pdo->exec('
            INSERT  INTO `users`
            VALUES  (7, \'john.doe@example.com\', \'7346598173659873\'),
                    (12, \'mary.jones@example.com\', \'2341341421\'),
                    (123, \'vitaly.d@example.com\', \'75647454\')');

        $rowsCount = $this->db->users->id->update([7, 123, 17], ['password_hash' => 'cbKBLVIWVW']);
        $this->assertEquals(2, $rowsCount);
    }

    public function testIsset()
    {
        $this->assertTrue(isset($this->db->user_roles->id[1]));
        $this->assertTrue(isset($this->db->user_roles->id[2]));
        $this->assertTrue(isset($this->db->user_roles->id[2]));
        $this->assertFalse(isset($this->db->user_roles->id[7]));
    }
}

