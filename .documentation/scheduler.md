#### What is it
The module allows you to schedule the execution of the message. Now support of the scheduler is only based on [RabbitMQ Delayed message plugin](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange).

#### Installation
> RabbitMQ with an installed plugin should be used as a transport.
> Plugin can be found [**here**](https://github.com/php-service-bus/demo/tree/v3.0/docker/rabbitmq/plugins)

```
composer req php-service-bus/scheduler
```

```php
$bootstrap->applyModules(
    SchedulerModule::rabbitMqWithSqlStorage(DatabaseAdapter::class)
);
```

After applying the module, you need to create an exchange to which the scheduled messages will be sent and associate it with the main:

```php
        yield $kernel->createTopic(
            AmqpExchange::delayed((string) \getenv('SCHEDULER_TOPIC')),
            TopicBind::create($mainExchange, \getenv('TRANSPORT_ROUTING_KEY'))
        );
```

After that you need to add a route for the message:

```php
        $kernel->registerDestinationForMessages(
            new AmqpTransportLevelDestination(
                    (string) \getenv('SCHEDULER_TOPIC'),
                    (string) \getenv('TRANSPORT_ROUTING_KEY')
            ),
            EmitSchedulerOperation::class
        );
```

> You also need to create a table in the database. @see [scheduler_registry.sql](https://github.com/php-service-bus/scheduler/blob/v5.0/src/Store/schema/scheduler_registry.sql)

#### Usage
A special provider has been implemented for working with scheduled tasks: [SchedulerProvider](https://github.com/php-service-bus/scheduler/blob/v5.0/src/SchedulerProvider.php). It contains the following methods:
* [schedule()](https://github.com/php-service-bus/scheduler/blob/v5.0/src/SchedulerProvider.php#L52): Schedule command execution;
* [cancel()](https://github.com/php-service-bus/scheduler/blob/v5.0/src/SchedulerProvider.php#L96): Cancel scheduled job.

#### Example

```php
final class TestService
{
    #[CommandHandler]
    public function handle(SomeCommand $command, KernelContext $context, SchedulerProvider $schedulerProvider): Promise
    {
        return $schedulerProvider->schedule(
            ScheduledOperationId::new(),
            new SomeScheduledCommand,
            datetimeInstantiator('+1 day'),
            $context
        );
    }
}
```