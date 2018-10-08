Table of contents
* [The list of environment variables](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_initialization.md#the-list-of-environment-variables)
* [Initialization](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_initialization.md#initialization)
* [Transport Configuration](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_initialization.md#transport-configuration)
* [Kernel configuration](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_initialization.md#kernel-configuration)
* [Creation of database schema](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_initialization.md#creation-of-database-schema)
* [Initialization demon example](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_initialization.md#initialization-demon-example)

#### The list of environment variables:
- ```APP_ENVIRONMENT```: Environment (*test*, *dev*, *prod*). Currently the difference is only on the container compilation level (to save it, or to recreate it each time (test, dev))
- ```APP_ENTRY_POINT_NAME```: The name of access point (name of your demon)
- ```TRANSPORT_CONNECTION_DSN```: DSN of transport connection. A format of type ```amqp://user:password@host:port```
- ```DATABASE_CONNECTION_DSN```: DSN of database connection. A format of type ```sqlite:///:memory:``` for tests and ```pgsql://user:password@host:port/database``` for real usage
- ```LOG_LEVEL```: Level of message logging for a logger by default (stdOut)
- ```AMP_LOG_COLOR```: (1/0) Do message levels need to be highlighted by different colors (a different color for each level is used)
- ```LOG_MESSAGE_PAYLOAD```: Does a message need to be logged completely. If switched off, data, contained in the message won’t be logged. Only the fact that a message was received/sent
- ```TRANSPORT_TOPIC```: Access point of message broker. In the RabbitMQ context – the name is exchange
- ```TRANSPORT_QUEUE```: A queue, which for demon to listen
- ```TRANSPORT_ROUTING_KEY```: Routing key for messages (topic -> queue)
- ```SENDER_DESTINATION_TOPIC```: Access point, into which the messages will be sent
- ```SENDER_DESTINATION_TOPIC_ROUTING_KEY```: Routing key for sent messages

At the demon’s initiation all indicated exchanges, queues will be created and routing keys will be filled in.

#### Initialization
An object of [Bootstrap](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php) class is in charge for the primary initialization of the application. Two ways of object creation are available: a method [withDotEnv](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L49) will upload environmental variables from a specified file; [withEnvironmentValues](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L99) is based on the presupposition, that the environment values are set


Available methods:
- [enableAutoImportSagas()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L80): Scans the project files and automatically registers all found sagas
- [enableAutoImportMessageHandlers()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L112): Scans the project files and automatically registers all found message processors
- [useAmqpExtTransport()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L136): Configuration for the RabbitMQ transport
- [useSqlStorage()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L156): Configuration for the SQL database
- [useCustomCacheDirectory()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L174): If not designated, the default directory will be used ([sys_get_temp_dir()](http://php.net/manual/en/function.sys-get-temp-dir.php))
- [importParameters()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L188): Imports the parameters into DI-containers
- [addExtensions()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L200): Registers user [Extension](https://symfony.com/doc/current/bundles/extension.html) in DI-container
- [addCompilerPasses()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L214): Registers user [CompilerPass](https://symfony.com/doc/current/service_container/compiler_passes.html) in DI-container
- [boot()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L122): Compiles DI-container
- [enableScheduler()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L94): Enable scheduler support. [Read more](https://github.com/mmasiukevich/service-bus/blob/master/doc/scheduler.md)

#### Transport Configuration
For the configuration of the transport layer is responsible [ServiceBusKernel](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php), in which is available [TransportConfigurator](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php)
- [addDefaultDestinations()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php#L61): Registers the default message delivery route
- [registerCustomMessageDestinations()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php#L80): Registers a specific delivery route for the message
- [addQueue()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php#L99): Creates a new queue (if it does not exist)
- [createTopic()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php#L120): Creates an exchange (if it does not exist)
- [bindQueue()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php#L155): bind a queue to exchange
- [bindTopic()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php#L140): bind exchange to exchange

#### Kernel configuration
- [monitorLoopBlock()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php#L101): Enable detection of blocking event loop
- [useDefaultStopSignalHandler()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php#L128): Use default handler for SIGINT(2) signal 
- [listen()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php#L152): Starting the application and subscribing to messages 
- [stop()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php#L174): Stop application after N milliseconds

#### Creation of database schema
**Important**: at the application initiation, a database scheme is not created. This is for the users to do.
Available for SQL fixture::
- [extensions.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/extensions.sql)
- [event_store_stream.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream.sql)
- [event_store_stream_events.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream_events.sql)
- [event_store_snapshots.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/event_store_snapshots.sql)
- [indexes.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/indexes.sql)
- [sagas_store.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/SagaStore/Sql/schema/sagas_store.sql)
- [indexes.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/SagaStore/Sql/schema/indexes.sql)
- [scheduler_registry.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/Scheduler/Store/Sql/schema/scheduler_registry.sql)
- [event_sourcing_indexes.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/Index/Storage/Sql/schema/event_sourcing_indexes.sql)

#### Initialization demon example

```php
<?php

declare(strict_types = 1);

namespace DocumentProcessing\Bin;

use Desperado\ServiceBus\Application\Bootstrap;
use Desperado\ServiceBus\Application\ServiceBusKernel;
use Desperado\ServiceBus\OutboundMessage\Destination;
use Desperado\ServiceBus\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\ServiceBus\Transport\AmqpExt\AmqpQueue;
use Desperado\ServiceBus\Transport\AmqpExt\AmqpTopic;
use Desperado\ServiceBus\Transport\QueueBind;
use ServiceBusDemo\App\ServiceBusDemoExtension;
use Symfony\Component\Debug\Debug;

include __DIR__ . '/vendor/autoload.php';

try
{
    /** @noinspection ForgottenDebugOutputInspection */
    Debug::enable();

    $container = Bootstrap::withDotEnv(__DIR__ . '/.env')
        ->useAmqpExtTransport((string) \getenv('TRANSPORT_CONNECTION_DSN'))
        ->useSqlStorage(AmpPostgreSQLAdapter::class, (string) \getenv('DATABASE_CONNECTION_DSN'))
        ->useCustomCacheDirectory(__DIR__ . '/cache')
        ->addExtensions(new ServiceBusDemoExtension())
        ->importParameters([
            'app.log_level' => (string) \getenv('LOG_LEVEL')
        ])
        ->enableAutoImportMessageHandlers([__DIR__ . '/src'])
        ->enableAutoImportSagas([__DIR__ . '/src'])
        ->boot();

    $kernel = new ServiceBusKernel($container);

    $transportConfigurator = $kernel->transportConfigurator();

    /** Main exchange and queue binds */

    $mainTopic = AmqpTopic::direct((string) \getenv('TRANSPORT_TOPIC'), true);
    $mainQueue = AmqpQueue::default((string) \getenv('TRANSPORT_QUEUE'), true);

    $transportConfigurator
        ->createTopic($mainTopic)
        ->addQueue($mainQueue)
        ->bindQueue(new QueueBind($mainQueue, $mainTopic, (string) \getenv('TRANSPORT_ROUTING_KEY')));

    $transportConfigurator->addDefaultDestinations(
        new Destination(
            (string) \getenv('SENDER_DESTINATION_TOPIC'),
            (string) \getenv('SENDER_DESTINATION_TOPIC_ROUTING_KEY')
        )
    );

    $kernel->listen($mainQueue);
}
catch(\Throwable $throwable)
{
    echo $throwable->getMessage(), \PHP_EOL, $throwable->getFile() . ':' . $throwable->getLine(), \PHP_EOL;
}

```
