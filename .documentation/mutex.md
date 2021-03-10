#### Supported types
* [InMemoryMutex](https://github.com/php-service-bus/mutex/blob/v5.0/src/InMemory/InMemoryMutex.php): Can only be used when working in one process;
* [RedisMutex](https://github.com/php-service-bus/mutex/blob/v5.0/src/Redis/RedisMutex.php): It can be used when several processes.

####  Usage
Mutexes are not created directly. Special factories are used for it:
* [InMemoryMutexFactory](https://github.com/php-service-bus/mutex/blob/v5.0/src/InMemory/InMemoryMutexFactory.php)
* [RedisMutexFactory](https://github.com/php-service-bus/mutex/blob/v5.0/src/Redis/RedisMutexFactory.php)

#### Examples
```php
$factory = new InMemoryMutexFactory();

Loop::run(
    function() use ($factory): \Generator
    {
        $lock = yield $factory->create('testKey')->acquire();

        /** Operation */

        yield $lock->release();

        Loop::stop();
    }
);
```