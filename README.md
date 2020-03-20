[![Build Status](https://travis-ci.com/v-dem/queasy-db.svg?branch=master)](https://travis-ci.com/v-dem/queasy-db) [![codecov](https://codecov.io/gh/v-dem/queasy-db/branch/master/graph/badge.svg)](https://codecov.io/gh/v-dem/queasy-db)

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
    'fetchMode' => PDO::FETCH_ASSOC
]);
```

#### Getting a single record from `users` table by `id` key:

```php
$user = $db->users->id[$id];
```

#### Inserting a record into `users` table:

```php
$db->users[] = [
    'email' => 'john.doe@example.com',
    'password_hash' => sha1('myverystrongpassword')
];
```

#### Getting last insert id:

```php
$newUserId = $db->id();
```

#### Updating a record in `users` table by `id` key:

```php
$db->users->id[$id] = [
    'password_hash' => sha1('mynewverystrongpassword')
]
```

#### Deleting a record in `users` table by `id` key:

```php
unset($db->users->id[$id]);
```


