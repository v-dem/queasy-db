[![Build Status](https://travis-ci.com/v-dem/queasy-db.svg?branch=master)](https://travis-ci.com/v-dem/queasy-db)
[![codecov](https://codecov.io/gh/v-dem/queasy-db/branch/master/graph/badge.svg)](https://codecov.io/gh/v-dem/queasy-db)
[![Total Downloads](https://poser.pugx.org/v-dem/queasy-db/downloads)](https://packagist.org/packages/v-dem/queasy-db)
[![License](https://poser.pugx.org/v-dem/queasy-db/license)](https://packagist.org/packages/v-dem/queasy-db)

# [Queasy PHP Framework](https://github.com/v-dem/queasy-app/) - Database

## Package `v-dem/queasy-db`

Database access classes. Some the most usual queries can be built automatically, more complex queries can be
added into database and/or tables config.

### Features

### Requirements

* PHP version 5.3 or higher
* Package `v-dem/queasy-helper`
* Package `v-dem/queasy-config` *(required for `dev` only)*
* Package `v-dem/queasy-log` *(required for `dev` only)*

### Documentation

See our [Wiki page](https://github.com/v-dem/queasy-db/wiki).

### Installation

    composer require v-dem/queasy-db:master-dev

### Usage

#### Notes

* `queasy\db\Db` class inherits `PDO` class, so any `PDO` methods can be called with it
* For some reasons original `PDOStatement` class is replaced by its implementation `queasy\db\Statement`
* You can use `setLogger()` method which accepts `Psr\Log\LoggerInterface` to log all queries

#### Initialization

Sample:
```php
$db = new queasy\db\Db([
    'connection' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'name' => 'test',
        'user' => 'test_user',
        'password' => 'test_password'
    ],
    'fetchMode' => PDO::FETCH_ASSOC // Default fetch mode for all queries
]);
```

#### Getting a single record from `users` table by `id` key:

```php
$user = $db->users->id[$userId];
```

It will generate the following query:

```sql
SELECT  *
FROM    `users`
WHERE   `id` = :id
```

#### Inserting a record into `users` table using associative array:

```php
$db->users[] = [
    'email' => 'john.doe@example.com',
    'password_hash' => sha1('myverystrongpassword')
];
```

It will generate the following query:

```sql
INSERT  INTO `users` (`email`, `password_hash`)
VALUES  (:email, :password_hash)
```

#### Inserting a record into `users` table by order (not recommended, keep in mind fields order):

```php
$db->users[] = [
    'john.doe@example.com',
    sha1('myverystrongpassword')
];
```

#### Inserting many records into `users` table using associative array (it will generate single `INSERT` statement):

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

#### Inserting many records into `users` table by order (not recommended; it will generate single `INSERT` statement):

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

#### Inserting many records into `users` table with field names denoted separately (it will generate single `INSERT` statement):

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

#### Getting last insert id (alias of `lastInsertId()` method):

```php
$newUserId = $db->id();
```

#### Updating a record in `users` table by `id` key:

```php
$db->users->id[$userId] = [
    'password_hash' => sha1('mynewverystrongpassword')
]
```

#### Deleting a record in `users` table by `id` key:

```php
unset($db->users->id[$userId]);
```

#### Get count of all records in `users` table *(I know this function is not very useful)*:

```php
$usersCount = count($db->users);
```

#### Using transactions:

```php
$db->trans(function() use($db) {
    // Run queries inside a transaction
});
```

#### Using `foreach` with a `users` table (obviously it will get all table records first):

```php
foreach($db->users as $user) {
    // Do something
}
```


