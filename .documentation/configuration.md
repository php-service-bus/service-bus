#### The list of environment variables

* `TRANSPORT_CONNECTION_DSN`: DSN of transport connection. A format of type `amqp://user:password@host:port`;
* `TRANSPORT_TOPIC`: Access point of message broker. In the RabbitMQ context – the name is exchange;
* `TRANSPORT_QUEUE`: The name of the queue to which the daemon will subscribe;
* `TRANSPORT_ROUTING_KEY`: Routing key for messages (topic -> queue);

* `SENDER_DESTINATION_TOPIC`: Access point, into which the messages will be sent by default;
* `SENDER_DESTINATION_TOPIC_ROUTING_KEY`: Routing key for sent messages;

* `DATABASE_CONNECTION_DSN`: DSN of database connection. A format of type `sqlite:///:memory:` for tests and `pgsql://user:password@host:port/database` for real usage;

* `APP_ENTRY_POINT_NAME`: The name of access point (name of your demon);
* `APP_ENVIRONMENT`: Environment (`test`, `dev`, `prod`). Currently the difference is only on the container compilation level (to save it, or to recreate it each time (`test`, `dev`));


* `LOG_LEVEL`: Level of message logging for a logger by default (stdOut);
* `AMP_LOG_COLOR`: Do message levels need to be highlighted by different colors (a different color for each level is used);

#### Bootstrap
An object of [Bootstrap](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/Bootstrap.php) class is in charge for the primary initialization of the application. Three ways of object creation are available:
* [withDotEnv()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/Bootstrap.php#L51): Create based on the environment parameters obtained from the `.env` file (via `symfony/dotenv component`);
* [withEnvironmentValues()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/Bootstrap.php#L77): Create based on environment variables. All parameters must be set in the environment;
* [create()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/Bootstrap.php#L98): Initialization without using environment variables.

Available methods:
* [applyModules()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/Bootstrap.php#L126): Configure additional module;
* [enableAutoImportMessageHandlers()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/Bootstrap.php#L142): Scans the project files and automatically registers all found message processors;
* [enableSimpleRetryStrategy()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/Bootstrap.php#L160): Enable default retry failed message strategy. [Read more about retries](./retries.md);
* [useCustomCacheDirectory()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/Bootstrap.php#L196): if not designated, the default directory will be used (`\sys_get_temp_dir()`);
* [importParameters()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/Bootstrap.php#L208): Imports the parameters into DI-container ([environment variable resolving](https://symfony.com/doc/current/configuration/env_var_processors.html) can also be used inside the container);
* [addExtensions()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/Bootstrap.php#L220): Registers user [Extension](https://symfony.com/doc/current/bundles/extension.html) in DI-container
* [addCompilerPasses()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/Bootstrap.php#L232): Registers user [CompilerPass](https://symfony.com/doc/current/service_container/compiler_passes.html) in DI-container;
* [boot()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/Bootstrap.php#L182): Compiles DI-container.

#### Kernel
Configuring Message Bus Subscription Options
Available methods:
* [createQueue()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/ServiceBusKernel.php#L77): Creates a new queue (if it doesn't exist);
* [createTopic()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/ServiceBusKernel.php#L90): Creates an exchange (if it doesn't exist);
* [monitorLoopBlock()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/ServiceBusKernel.php#L107): Enable detection of blocking event loop (**DO NOT USE IN PRODUCTION environment**);
* [enableGarbageCleaning()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/ServiceBusKernel.php#L124): Enable periodic forced launch of the garbage collector;
* [useDefaultStopSignalHandler()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/ServiceBusKernel.php#L148): Use default handler for `SIGINT`/`SIGTERM` signals;
* [stopAfter()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/ServiceBusKernel.php#L186): Shut down after N seconds;
* [registerEndpointForMessages()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/ServiceBusKernel.php#L211): Apply specific route to deliver a message;
* [registerDestinationForMessages()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/ServiceBusKernel.php#L233): Like the registerEndpointForMessages method, it adds a custom message delivery route. The only difference is that the route is specified for the current application transport;
* [run()](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Application/ServiceBusKernel.php#L98): Start listening to the specified queues.

#### Example

```php
#!/usr/bin/env php
<?php

declare(strict_types = 1);

use Amp\Loop;
use ServiceBus\Application\ServiceBusKernel;
use ServiceBus\Transport\Amqp\AmqpExchange;
use ServiceBus\Transport\Amqp\AmqpQueue;
use ServiceBus\Transport\Common\QueueBind;
use ServiceBus\Transport\Module\PhpInnacleTransportModule;
use ServiceBus\Application\DependencyInjection\Compiler\Logger\StdOutLoggerCompilerPass;
use ServiceBus\Application\Bootstrap;
use ServiceBus\Storage\Module\SqlStorageModule;
use ServiceBus\Sagas\Module\SagaModule;
use ServiceBus\Storage\Common\DatabaseAdapter;
use ServiceBus\Scheduler\Module\SchedulerModule;
use ServiceBus\Transport\Common\TopicBind;
use ServiceBus\Transport\Amqp\AmqpTransportLevelDestination;
use ServiceBus\Scheduler\Contract\EmitSchedulerOperation;
use ServiceBus\EventSourcing\Module\EventSourcingModule;
use ServiceBus\Application\DependencyInjection\Compiler\Retry\SimpleRetryCompilerPass;

include __DIR__ . '/../vendor/autoload.php';

$bootstrap = Bootstrap::withDotEnv(rootDirectoryPath: __DIR__ . '/..', envFilePath: __DIR__ . '/../.env')
    ->addExtensions(extensions: new \AppExtension())
    ->useCustomCacheDirectory(cacheDirectoryPath: __DIR__ . '/../cache')
    ->enableAutoImportMessageHandlers(directories: [__DIR__ . '/../src'], excludedFiles: []);

$bootstrap->addCompilerPasses(
    new StdOutLoggerCompilerPass(),
    new SimpleRetryCompilerPass(maxRetryCount: 10, retryDelay: 1)
);

$bootstrap->applyModules(
    SqlStorageModule::postgreSQL(connectionDSN: (string) \getenv('DATABASE_CONNECTION_DSN')),
    SagaModule::withSqlStorage(databaseAdapterServiceId: DatabaseAdapter::class)->enableAutoImportSagas(
        directories: [__DIR__ . '/../src'],
        excludedFiles: []
    ),
    EventSourcingModule::withSqlStorage(databaseAdapterServiceId: DatabaseAdapter::class),
    SchedulerModule::rabbitMqWithSqlStorage(databaseAdapterServiceId: DatabaseAdapter::class),
    new PhpInnacleTransportModule(
        connectionDSN: (string) \getenv('TRANSPORT_CONNECTION_DSN'),
        defaultDestinationExchange: (string) \getenv('SENDER_DESTINATION_TOPIC'),
        defaultDestinationRoutingKey: (string) \getenv('SENDER_DESTINATION_TOPIC_ROUTING_KEY')
    )
);

/** @noinspection PhpUnhandledExceptionInspection */
$container = $bootstrap->boot();

Loop::run(
    static function() use ($container): \Generator
    {
        try
        {
            $mainExchange = AmqpExchange::direct((string) \getenv('TRANSPORT_TOPIC'))->makeDurable();
            $mainQueue    = AmqpQueue::default((string) \getenv('TRANSPORT_QUEUE'))->makeDurable();

            $kernel = (new ServiceBusKernel($container))
                ->useDefaultStopSignalHandler(stopDelay: 3);

            yield $kernel->createQueue(
                queue: $mainQueue,
                binds: new QueueBind(
                    destinationTopic: $mainExchange,
                    routingKey: (string) \getenv('TRANSPORT_ROUTING_KEY')
                )
            );

            /** Scheduler exchange */
            yield $kernel->createTopic(
                topic: AmqpExchange::delayed((string) \getenv('SCHEDULER_TOPIC')),
                binds: new TopicBind(
                    destinationTopic: $mainExchange,
                    routingKey: (string) \getenv('TRANSPORT_ROUTING_KEY')
                )
            );

            /** Add custom message route for scheduled operations */
            $kernel->registerDestinationForMessages(
                new AmqpTransportLevelDestination(
                    (string) \getenv('SCHEDULER_TOPIC'),
                    (string) \getenv('TRANSPORT_ROUTING_KEY')
                ),
                EmitSchedulerOperation::class
            );

            yield $kernel->run($mainQueue);
        }
        catch(\Throwable $throwable)
        {
            echo $throwable->getMessage(), \PHP_EOL;
            exit;
        }
    }
);

```