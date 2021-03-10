#### Installation
```
composer req php-service-bus/storage
```
#### About adapters
##### PostgreSQL
Non-blocking adapter that supports connection pooling. Implemented based on [Async Postgres client](https://github.com/amphp/postgres).

*Connections pool*:
> A database connection pool is a set of pre-opened database connections used to provide a connection at the moment it is required.  
> Used to improve performance when working with databases. In addition to performance, it also allows you to work competitively with transactions (each transaction is performed in a separate connection)

```php
$bootstrap->applyModules(
    SqlStorageModule::postgreSQL($connectionDSN)->enableLogger()
);
```

`$connectionDSN`: contains the PostgreSQL connection string. Example: `pgsql://user:password@host:5432/dbName?max_connections=100&idle_timeout=60`
* `idle_timeout`: Number of seconds until idle connections are removed from the pool
* `max_connections`: Maximum number of active connections in the pool

##### DoctrineDBAL

This adapter only supports in memory SQLite and is intended solely **for testing only** (It is blocking and, if used, breaks the operation of the event loop)

```php
$bootstrap->applyModules(
    SqlStorageModule::inMemory()->enableLogger()
);
```

#### Common interfaces
Each adapter implements interfaces:
* [DatabaseAdapter](https://github.com/php-service-bus/storage/blob/v5.0/src/Common/DatabaseAdapter.php): Base adapter Interface;
* [QueryExecutor](https://github.com/php-service-bus/storage/blob/v5.0/src/Common/QueryExecutor.php): SQL query execution interface;
* [BinaryDataDecoder](https://github.com/php-service-bus/storage/blob/v5.0/src/Common/BinaryDataDecoder.php): Binary data decoding interface (bytea, etc);
* [Transaction:](https://github.com/php-service-bus/storage/blob/v5.0/src/Common/Transaction.php): Begin transaction Interface.

#### ResultSet

[ResultSet](https://github.com/php-service-bus/storage/blob/v5.0/src/Common/ResultSet.php) is used to work with the `execute()` method result. Represents an iterator with several additional methods:
* [advance()](https://github.com/php-service-bus/storage/blob/v5.0/src/Common/ResultSet.php#L30):
* [getCurrent()](https://github.com/php-service-bus/storage/blob/v5.0/src/Common/ResultSet.php#L40):
* [lastInsertId()](https://github.com/php-service-bus/storage/blob/v5.0/src/Common/ResultSet.php#L49):
* [affectedRows()](https://github.com/php-service-bus/storage/blob/v5.0/src/Common/ResultSet.php#L56):

#### Helpers

In addition to adapters, some helpers are also implemented:

* [fetchAll()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L37): Transform [ResultSet](https://github.com/php-service-bus/storage/blob/v5.0/src/Common/ResultSet.php) iterator to array (only 1 item);
* [fetchOne()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L64): Transform [ResultSet](https://github.com/php-service-bus/storage/blob/v5.0/src/Common/ResultSet.php) iterator to array (Not recommended for use on large amounts of data);
* [sequence()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L101): Returns the value of the specified sequence (string);
* [find()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L136): Create & execute SELECT query;
* [remove()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L169): Create & execute DELETE query;
* [unescapeBinary()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L247): Unescape binary data;
* [buildQuery()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L203): Create query from specified parameters;
* [equalsCriteria()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L275): Create equals criteria;
* [notEqualsCriteria()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L292): Create not equals criteria;
* [queryBuilder()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L305): Create query builder;
* [selectQuery()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L315): Create select query (for PostgreSQL);
* [updateQuery()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L329): Create update query (for PostgreSQL);
* [deleteQuery()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L339): Create delete query (for PostgreSQL);
* [insertQuery()](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/functions.php#L353): Create insert query (for PostgreSQL);

#### Active record implementation
The module also contains a simple implementation of the ActiveRecord pattern.

##### Examples
```php
/**
 * @property string   $title
 * @property string   $description
 * @property-read int $pk
 */
final class QwertyTable extends Table
{
    /**
     * @inheritDoc
     */
    protected static function tableName(): string
    {
        return 'qwerty';
    }
}
```

```php
$adapter = new AmpPostgreSQLAdapter(
    new StorageConfiguration('pgsql://postgres:123456789@localhost:5432/test')
);

Loop::run(
    static function() use ($adapter): \Generator
    {
        /** @var array<int, \QwertyTable> $entries */
        $entries = yield \QwertyTable::findBy($adapter, [equalsCriteria('id', 'someId')], 100);

        /** @var \QwertyTable|null $entry */
        $entry = yield \QwertyTable::find($adapter, 'someId');

        /** @var \QwertyTable|null $entry */
        $entry = yield \QwertyTable::findOneBy($adapter, [equalsCriteria('title', 'expected title')]);
    }
);
```

```php
$adapter = new AmpPostgreSQLAdapter(
    new StorageConfiguration('pgsql://postgres:123456789@localhost:5432/test')
);

Loop::run(
    static function() use ($adapter): \Generator
    {
        /** @var \QwertyTable $entry */
        $entry = yield \QwertyTable::new($adapter, ['title' => 'some title', ['description' => 'some description']]);

        $entry->title = 'new title';

        yield $entry->save();
        yield $entry->remove();
    }
);
```

#### Finders
To simplify the search for information in the database, several [finders](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/Finder/SqlFinder.php) are also implemented:
* [SimpleSqlFinder](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/Finder/SimpleSqlFinder.php)
* [CachedSqlFinder](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/Finder/CachedSqlFinder.php): Search a collection with caching the result.

#### Migrations

Migrations are always required to work with SQL. There is a small package that will allow you to implement it.

Base class of migrations - [Migration](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/Migration/Migration.php). Every migration must be inherited from it.
[SqlMigrationLoader](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/Migration/SqlMigrationLoader.php) is used to find all migrations in a directory, and [SqlMigrationProcessor](https://github.com/php-service-bus/storage/blob/v5.0/src/Sql/Migration/SqlMigrationProcessor.php) is used to perform migrations.

> A usage example is in the [test app](https://github.com/php-service-bus/demo/tree/v5.0/tools/migrations). Also the default migrations are added when initializing the project with the [skeletone](https://github.com/php-service-bus/skeleton).
