# Welcome to the queasy-core wiki!

Queasy PHP framework was developed to help with small projects whose don't require a lot of features implemented in other big, great and nice frameworks like Laravel.

1. [Intro](https://github.com/v-dem/queasy-core/wiki/Intro)
2. [Configs](https://github.com/v-dem/queasy-core/wiki/Configs)
3. [Logs](https://github.com/v-dem/queasy-core/wiki/Logs)
4. [Routes and Controllers](https://github.com/v-dem/queasy-core/wiki/Routes)
5. [Database and Models](https://github.com/v-dem/queasy-core/wiki/Database-and-Models)
6. [Forms and Validation](https://github.com/v-dem/queasy-core/wiki/Forms)
7. [Internationalization](https://github.com/v-dem/queasy-core/wiki/Internationalization)
8. [Events and Listeners](https://github.com/v-dem/queasy-core/wiki/Events)

## Requirements
* PHP 5.3 - Newer PHP versions surely can be used too. This framework doesn't use any things came in newer versions, so it is useful even within PHP 5.3 hostings.
* PDO - for database access.
* Apache Httpd - to serve .htaccess for human-readable URLs.

## Installation
* Install [Composer](http://getcomposer.org/download/)
* Run `composer create-project --stability=dev --prefer-dist v-dem/queasy-app YOUR_PROJECT_NAME`
* Copy `queasy-config.php.sample` to `queasy-config.php` and modify its settings due to your system configuration.

## Features

### Quick
* Much faster than other micro frameworks.

### Easy
* No complex things like DI or IoC used. Just a standard OOP. So debugging is very easy and source code is clean to understand.

### Small
* Just a several tens of files. And they are loaded only when needed.

### Functional
* Supports complex configurations with ability to load from different files.
* Supports internationalization from a box.
* Forms validation from a box too.
* Built-in logger, it has to be PSR-compatible in future.
* Database access is very easy for easy queries (like INSERT, DELETE, UPDATE or SELECT by a single field), more complex queries can be configured in config files.
* REST support. Every Controller should respond to HTTP methods like GET, POST, PUT, DELETE etc - no routes required.

### MVC

## Folders structure
* **`/app`** Default folder for custom application files
* * **`/app/controllers`** Controllers
* * **`/app/models`** Models
* * **`/app/forms`** Forms
* * **`/app/events`** Events
* * **`/app/listeners`** Event listeners
* * **`/app/App.php`** Main application class
* **`/public`** Default folder for public resources like CSS, JS, images etc.
* **`/public/index.php`** Queasy loader
* **`/i18n`** Default folder for translations
* **`/logs`** Default folder for log files
* **`/views`** Default folder for views
* **`/vendor`** Contains Composer classes, including Queasy core files

