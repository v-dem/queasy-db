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

class FieldTest extends TestCase
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
        $this->db->query('
            INSERT  INTO `users` (`id`, `name`, `email`)
            VALUES  (1, \'vitaly\', \'vitaly_demyanenko@yahoo.com\')
        ');
        $this->db->query('
            INSERT  INTO `users` (`id`, `name`, `email`)
            VALUES  (2, \'john\', \'john.doe@example.com\')
        ');
        $this->db->query('
            INSERT  INTO `users` (`id`, `name`, `email`)
            VALUES  (7, \'mary\', \'mary.campbell@microsoft.com\')
        ');
    }

    public function tearDown(): void
    {
        $this->db = null;
    }

    public function testDefault()
    {
        $user = $this->db->users->id[7];

        $this->assertEquals('mary', $user['name']);
    }
}

