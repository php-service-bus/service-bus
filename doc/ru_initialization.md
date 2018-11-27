Оглавление
* [Список параметров окружения](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_initialization.md#%D0%A1%D0%BF%D0%B8%D1%81%D0%BE%D0%BA-%D0%BF%D0%B0%D1%80%D0%B0%D0%BC%D0%B5%D1%82%D1%80%D0%BE%D0%B2-%D0%BE%D0%BA%D1%80%D1%83%D0%B6%D0%B5%D0%BD%D0%B8%D1%8F)
* [Инциализация](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_initialization.md#%D0%98%D0%BD%D1%86%D0%B8%D0%B0%D0%BB%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D1%8F)
* [Конфигурация транспорта](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_initialization.md#%D0%9A%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B0%D1%86%D0%B8%D1%8F-%D1%82%D1%80%D0%B0%D0%BD%D1%81%D0%BF%D0%BE%D1%80%D1%82%D0%B0)
* [Создание схемы базы данных](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_initialization.md#%D0%A1%D0%BE%D0%B7%D0%B4%D0%B0%D0%BD%D0%B8%D0%B5-%D1%81%D1%85%D0%B5%D0%BC%D1%8B-%D0%B1%D0%B0%D0%B7%D1%8B-%D0%B4%D0%B0%D0%BD%D0%BD%D1%8B%D1%85)
* [Пример инициализации](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_initialization.md#%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80-%D0%B8%D0%BD%D0%B8%D1%86%D0%B8%D0%B0%D0%BB%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D0%B8-%D0%B4%D0%B5%D0%BC%D0%BE%D0%BD%D0%B0)

#### Список параметров окружения:
- ```APP_ENVIRONMENT```: Окружение (*test*, *dev*, *prod*). На текущий момент разница только на уровне компиляции контейнера (сохранять его, или пересоздавать каждый раз (*test*, *dev*))
- ```APP_ENTRY_POINT_NAME```: название точки входа (произвольное название вашего демона)
- ```TRANSPORT_CONNECTION_DSN```: DSN подключения к транспорту. Формат вида ```amqp://user:password@host:port```
- ```DATABASE_CONNECTION_DSN```: DSN подключения к базе данных. Формат вида ```sqlite:///:memory:``` для тестов и ```pgsql://user:password@host:port/database``` для реального использования
- ```LOG_LEVEL```: Уровень логгирования сообщений для логгера по умолчанию (stdOut)
- ```AMP_LOG_COLOR```: (1/0) Нужно ли подсвечивать уровни сообщения разными цветами (в зависимости от уровня применяется свой цвет)
- ```LOG_MESSAGE_PAYLOAD```: Необходимо ли логировать сообщение целиком, включая заголовки. Если выключено, то данные, содержащиеся в сообщении залогированы не будут. Только факт того, что сообщение было получено\отправлено
- ```TRANSPORT_TOPIC```: Точка входа брокера сообщений. В контексте RabbitMQ - название exchange
- ```TRANSPORT_QUEUE```: Очередь, которую будет слушать демон
- ```TRANSPORT_ROUTING_KEY```: ключ роутинга для сообщений (topic -> queue)
- ```SENDER_DESTINATION_TOPIC```: Точка входа, в которую будут отправляться сообщения
- ```SENDER_DESTINATION_TOPIC_ROUTING_KEY```: Ключ роутинга для отправляемых сообщений

При старте демона будут созданы все указанные exchange'ы, queue'ы и проставлены routing keys.

#### Инциализация
За первоначальную инциализацию приложения отвечает объект класса [Bootstrap](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php). На выбор доступны 2 варианта создания объекта: метод [withDotEnv](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L49) загрузит параметры окружения из указанного файла; [withEnvironmentValues](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L99) предполагает, что параметры окружения уже загружены кем-то.

Помимо всего прочего, доступны следующие методы:
- [enableAutoImportSagas()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L81): Сканирует файлы проекта и автоматически регистрирует все найденные саги
- [enableAutoImportMessageHandlers()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L119): Сканирует файлы проекта и автоматически регистрирует все найденные обработчики сообщений
- [useRabbitMqTransport()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L151): Конфигурирует RabbitMQ транспорт
- [useSqlStorage()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L179): Конфигурация SQL базы данных
- [useCustomCacheDirectory()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L197): Если не указан, то будет использоваться директория по умолчанию (sys_get_temp_dir)
- [importParameters()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L211): Импортирует параметры в DI-контейнер
- [addExtensions()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L223): Регистрирует пользовательский [Extension](https://symfony.com/doc/current/bundles/extension.html) в DI-контейнере
- [addCompilerPasses()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L237): Регистрирует пользовательский [CompilerPass](https://symfony.com/doc/current/service_container/compiler_passes.html)
- [boot()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L135): Компилирует DI-контейнер
- [enableScheduler()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L101): Включает поддержку планировщика. [Подробнее](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_scheduler.md)

#### Конфигурация транспорта
За конфигурацию транспортного уровня отвечает [ServiceBusKernel](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php), в котором доступен [transport()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php#L230)
- [createQueue()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Transport/Transport.php#L54): Создаёт новую очередь (если не существует)
- [createTopic()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Transport/Transport.php#L39): Создаёт exchange (если не существует)

#### Конфигурация кернела
- [monitorLoopBlock()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php#L80): Включить определение блокировки чем-либо эвент лупа
- [enableGarbageCleaning()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php#L95): Периодически принудительно вызывает сборщик мусора
- [useDefaultStopSignalHandler()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php#L116):  Использовать обработчик для сигналов SIGINT/SIGTERM по умолчанию
- [stopAfter()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php#L150): Завершить работу демона через N секунд
- [registerMessageCustomEndpoint()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php#L219): Указания специфичного маршрута доставки сообщения
- [stopWhenFilesChange()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php#L179):

#### Создание схемы базы данных
**Важно**: при старте приложения не создаётся схема базы данных. Это отдано на откуп пользователям.
Доступные для SQL фикстуры:
- [extensions.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/extensions.sql)
- [event_store_stream.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream.sql)
- [event_store_stream_events.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream_events.sql)
- [event_store_snapshots.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/event_store_snapshots.sql)
- [indexes.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/indexes.sql)
- [sagas_store.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/SagaStore/Sql/schema/sagas_store.sql)
- [indexes.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/SagaStore/Sql/schema/indexes.sql)
- [scheduler_registry.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/Scheduler/Store/Sql/schema/scheduler_registry.sql)
- [event_sourcing_indexes.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/Index/Storage/Sql/schema/event_sourcing_indexes.sql)

#### Пример инициализации демона

```php
#!/usr/bin/env php
<?php

declare(strict_types = 1);

namespace ServiceBusDemo\Bin;

use Amp\Loop;
use Desperado\ServiceBus\Application\Bootstrap;
use Desperado\ServiceBus\Application\ServiceBusKernel;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpExchange;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpQueue;
use Desperado\ServiceBus\Infrastructure\Transport\QueueBind;
use Desperado\ServiceBus\Infrastructure\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use ServiceBusDemo\App\ServiceBusDemoExtension;

include __DIR__ . '/../vendor/autoload.php';

/** @var \Symfony\Component\DependencyInjection\Container $container */
$container = Bootstrap::withDotEnv(__DIR__ . '/../.env')
    ->useRabbitMqTransport(
        (string) \getenv('TRANSPORT_CONNECTION_DSN'),
        (string) \getenv('TRANSPORT_TOPIC'),
        (string) \getenv('TRANSPORT_ROUTING_KEY')
    )
    ->useSqlStorage(AmpPostgreSQLAdapter::class, (string) \getenv('DATABASE_CONNECTION_DSN'))
    ->useCustomCacheDirectory(__DIR__ . '/../cache')
    ->addExtensions(new ServiceBusDemoExtension())
    ->importParameters([
        'app.log_level' => (string) \getenv('LOG_LEVEL')
    ])
    ->enableAutoImportMessageHandlers([__DIR__ . '/../src'])
    ->enableAutoImportSagas([__DIR__ . '/../src'])
    ->boot();

$kernel = new ServiceBusKernel($container);

Loop::run(
    static function() use ($kernel): \Generator
    {
        $mainExchange = AmqpExchange::direct((string) \getenv('TRANSPORT_TOPIC'), true);
        $mainQueue    = AmqpQueue::default((string) \getenv('TRANSPORT_QUEUE'), true);

        yield $kernel
            ->transport()
            ->createQueue(
                $mainQueue,
                new QueueBind($mainExchange,
                    (string) \getenv('TRANSPORT_ROUTING_KEY'))
            );

        $kernel
            ->monitorLoopBlock()
            ->enableGarbageCleaning()
            ->useDefaultStopSignalHandler()
            ->stopAfter(30);

        yield $kernel->entryPoint()->listen($mainQueue);
    }
);

```
