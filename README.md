[![Codacy Badge](https://api.codacy.com/project/badge/Grade/0d4762b3b45e48c69d13687cd786e0ca)](https://app.codacy.com/manual/v-dem/queasy-db?utm_source=github.com&utm_medium=referral&utm_content=v-dem/queasy-db&utm_campaign=Badge_Grade_Dashboard)
[![codecov](https://codecov.io/gh/v-dem/queasy-db/branch/master/graph/badge.svg)](https://codecov.io/gh/v-dem/queasy-db)
[![Total Downloads](https://poser.pugx.org/v-dem/queasy-db/downloads)](https://packagist.org/packages/v-dem/queasy-db)
[![Latest Stable Version](https://img.shields.io/github/v/release/v-dem/queasy-db)](https://packagist.org/packages/v-dem/queasy-db)
[![License](https://poser.pugx.org/v-dem/queasy-db/license)](https://packagist.org/packages/v-dem/queasy-db)

# [QuEasy PHP Framework](https://github.com/v-dem/queasy-framework/) - Database

## Package `v-dem/queasy-db`

QuEasy DB is a set of database access classes for CRUD operations.
Some of the most usual queries can be built automatically (like `SELECT` by unique field value/values, `UPDATE`, `INSERT` and `DELETE`).
More complex queries can be defined in database and/or tables config.
The main goal is to separate `SQL` queries out of `PHP` code and provide an easy way for CRUD operations.

### Features

* QuEasy DB extends `PDO` class, so any project which uses `PDO` can be seamlessly moved to use QuEasy DB.
* Simple CRUD database operations in just one PHP code row.
* Separating SQL queries from PHP code.

### Requirements

*   PHP version 5.3 or higher

### Installation

    composer require v-dem/queasy-db

It will also install `v-dem/queasy-helper`.

### Usage

#### Notes

*   You can use `setLogger()` method which accepts `Psr\Log\LoggerInterface` implementation to log all queries, by default `Psr\Log\NullLogger` is used.
*   By default error mode is set to `PDO::ERRMODE_EXCEPTION` (as in PHP8) if another mode is not set in `$options`.

##### IMPORTANT!

*   For MySQL Server need to set option `PDO::MYSQL_ATTR_INIT_COMMAND` to `SET GLOBAL SQL_MODE=ANSI_QUOTES` or run same query before calling DB-specific methods.
*   For MSSQL Server need to run `SET QUOTED_IDENTIFIER ON` or `SET ANSI_DEFAULTS ON` query before calling DB-specific methods.

#### Initialization

Sample:
```php
$db = new queasy\db\Db(
    [
        'dsn' => 'pgsql:host=localhost;dbname=test',
        'user' => 'test_user',
        'password' => 'test_password',
        'options' => [
            ...options...
        ]
    ]
);
```

Or:
```php
$db = new queasy\db\Db(
    [
        'connection' => [
            'dsn' => 'pgsql:host=localhost;dbname=test',
            'user' => 'test_user',
            'password' => 'test_password',
            'options' => [
                ...options...
            ]
        ]
    ]
);
```

Or PDO-way:
```php
$db = new queasy\db\Db('pgsql:host=localhost;dbname=test', 'test_user', 'test_password', $options);
```

* Fourth argument (`$options`) is optional, will be passed to `PDO::prepare()`

#### Get all records from `users` table

```php
$users = $db->users->all();
```

#### Using `foreach` with `users` table

```php
foreach ($db->users as $user) {
    // Do something
}
```

#### Get single record from `users` table by `id` key

```php
$user = $db->users->id[$userId];
```

It's possible to use `select()` method to pass PDO options; `select()` returns PDOStatement instance:
```php
$users = $db->users->id->select($userId, $options);
```

#### Get multiple records

```php
$users = $db->users->id[[$userId1, $userId2]];
```

#### Insert a record into `users` table using associative array

```php
$db->users[] = [
    'email' => 'john.doe@example.com',
    'password_hash' => sha1('myverystrongpassword')
];
```

#### Insert a record into `users` table by fields order

```php
$db->users[] = [
    'john.doe@example.com',
    sha1('myverystrongpassword')
];
```

#### Insert many records into `users` table using associative array (it will generate single `INSERT` statement)

```php
$db->users[] = [
    [
        'email' => 'john.doe@example.com',
        'password_hash' => sha1('myverystrongpassword')
    ], [
        'email' => 'mary.joe@example.com',
        'password_hash' => sha1('herverystrongpassword')
    ]
];
```

#### Insert many records into `users` table by order

```php
$db->users[] = [
    [
        'john.doe@example.com',
        sha1('myverystrongpassword')
    ], [
        'mary.joe@example.com',
        sha1('herverystrongpassword')
    ]
];
```

#### Inserting many records into `users` table with field names denoted separately

```php
$db->users[] = [
    [
        'email',
        'password_hash'
    ], [
        [
            'john.doe@example.com',
            sha1('myverystrongpassword')
        ], [
            'mary.joe@example.com',
            sha1('herverystrongpassword')
        ]
    ]
];
```

Also it's possible to use `insert()` method (in the same way as above) when need to pass PDO options; returns last insert id for single insert and number of inserted rows for multiple inserts:
```php
$userId = $db->users->insert([
    'email' => 'john.doe@example.com',
    'password_hash' => sha1('myverystrongpassword')
], $options);
```

```php
$insertedRowsCount = $db->users->insert([
    [
        'email' => 'john.doe@example.com',
        'password_hash' => sha1('myverystrongpassword')
    ], [
        'email' => 'mary.joe@example.com',
        'password_hash' => sha1('herverystrongpassword')
    ]
], $options);
```

* Second argument (`$options`) is optional, will be passed to `PDO::prepare()`

#### Get last insert id (alias of `lastInsertId()` method)

```php
$newUserId = $db->id();
```

#### Update a record in `users` table by `id` key

```php
$db->users->id[$userId] = [
    'password_hash' => sha1('mynewverystrongpassword')
]
```

```php
$updatedRowsCount = $db->users->id->update($userId, [
    'password_hash' => sha1('mynewverystrongpassword')
], $options);
```

* Third argument (`$options`) is optional, will be passed to `PDO::prepare()`

#### Update multiple records

```php
$db->users->id[[$userId1, $userId2]] = [
    'is_blocked' => true
]
```

#### Delete a record in `users` table by `id` key

```php
unset($db->users->id[$userId]);
```

#### Delete multiple records

```php
unset($db->users->id[[$userId1, $userId2]]);
```

```php
$deletedRowsCount = $db->users->id->delete([[$userId1, $userId2]], $options);
```

* Second argument (`$options`) is optional, will be passed to `PDO::prepare()`

#### Get count of all records in `users` table

```php
$usersCount = count($db->users);
```

#### Using transactions

```php
$db->trans(function() use($db) {
    // Run queries inside a transaction, for example:
    $db->users[] = [
        'john.doe@example.com',
        sha1('myverystrongpassword')
    ];
});
```
* On exception transaction is rolled back and exception re-thrown to outer code.

#### Run custom query (returns `PDOStatement`)

```php
$users = $db->run('
    SELECT  *
    FROM    "users"
    WHERE   "name" LIKE concat(\'%\', :searchName, \'%\')',
    [
        ':searchName' => 'John'
    ],
    $options
)->fetchAll();
```

* Third argument (`$options`) is optional, will be passed to `PDO::prepare()`

#### Run query predefined in configuration

This feature can help keep code cleaner and place SQL code outside PHP, somewhere in config files.

```php
$db = new queasy\db\Db(
    [
        'connection' => [
            'dsn' => 'pgsql:host=localhost;dbname=test',
            'user' => 'test_user',
            'password' => 'test_password'
        ],
        'queries' => [
            'searchUsersByName' => [
                'sql' => '
                    SELECT  *
                    FROM    "users"
                    WHERE   "name" LIKE concat(\'%\', :searchName, \'%\')',
                'returns' => Db::RETURN_ALL
            ]
        ]
    ]
);

$users = $db->searchUsersByName([
    'searchName' => 'John'
]);
```

* Possible values for `returns` option are `Db::RETURN_STATEMENT` (default, returns `PDOStatement` instance), `Db::RETURN_ONE`, `Db::RETURN_ALL`, `Db::RETURN_VALUE`

Also it is possible to group predefined queries by tables:

```php
$db = new queasy\db\Db(
    [
        'connection' => [
            'dsn' => 'pgsql:host=localhost;dbname=test',
            'user' => 'test_user',
            'password' => 'test_password'
        ],
        'tables' => [
            'users' => [
                'searchByName' => [
                    'sql' => '
                        SELECT  *
                        FROM    "user_roles"
                        WHERE   "name" LIKE concat(\'%\', :searchName, \'%\')',
                    'returns' => Db::RETURN_ALL
                ]
            ]
        ]
    ]
);

$users = $db->users->searchByName([
    'searchName' => 'John'
]);
```

#### Using `v-dem/queasy-db` together with `v-dem/queasy-config` and `v-dem/queasy-log`

`config.php:`
```php
return [
    'db' => [
        'connection' => [
            'dsn' => 'pgsql:host=localhost;dbname=test',
            'user' => 'test_user',
            'password' => 'test_password'
        ],
        'tables' => [
            'users' => [
                'searchByName' => [
                    'sql' => '
                        SELECT  *
                        FROM    "users"
                        WHERE   "name" LIKE concat(\'%\', :searchName, \'%\')',
                    'returns' => Db::RETURN_ALL
                ]
            ]
        ]
    ],

    'logger' => [
        [
            'class' => queasy\log\ConsoleLogger::class,
            'minLevel' => Psr\Log\LogLevel::DEBUG
        ]
    ]
];
```

Initializing:
```php
$config = new queasy\config\Config('config.php'); // Can be also INI, JSON or XML

$logger = new queasy\log\Logger($config->logger);

$db = new queasy\db\Db($config->db);
$db->setLogger($logger);

$users = $db->users->searchByName([
    'searchName' => 'John'
]);
```

* All queries will be logged with `Psr\Log\LogLevel::DEBUG` level. Also it's possible to use any other logger class compatible with PSR-3.

