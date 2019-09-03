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

use queasy\db\Db;
use queasy\db\DbException;

class TableTest extends TestCase
{
    private $db;

    public function setUp(): void
    {
        $this->db = new Db(['fetchMode' => Db::FETCH_ASSOC]);
        $this->db->query('
            CREATE  TABLE `users` (
                    `id`        integer     not null primary key asc,
                    `name`      text        not null,
                    `email`     text        not null unique
            )
        ');
    }

    public function tearDown(): void
    {
        $this->db = null;
    }

    public function testInsert()
    {
        $this->db->users[] = [15, 'john', 'john.doe@example.com'];

        $user = $this->db->users->id[15];

        $this->assertEquals('john', $user['name']);
    }

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
}

