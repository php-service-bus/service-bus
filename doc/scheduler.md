Оглавление
* [Особенности реализации]()
* [Конфигурация]()
* [Использование]()
* [Отмена команды]()

#### Особенности реализации
На данный момент планировщик работает **только** с [RabbitMQ](https://www.rabbitmq.com/) с установленным [rabbitmq-delayed-message-exchange](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange) плагином.

#### Конфигурация
Во время [инициализации демона](https://github.com/mmasiukevich/service-bus/blob/master/doc/initialization.md) у объекта класс [Bootstrap](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php) необходимо вызвать метод [enableScheduler()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L94). 
Данный метод регистрирует сервисы, необходимые для работы планировщика, а так же добавляет 4 обработчика для сообщений.

Помимо всего прочего необходимо дополнительно сконфигурировать транспортный уровень:

```php
    $schedulerTopic = AmqpTopic::delayed('scheduler');

    $transportConfigurator
        ->createTopic($schedulerTopic)
        ->bindTopic(new TopicBind($mainTopic, $schedulerTopic, (string) \getenv('TRANSPORT_ROUTING_KEY')));

    $transportConfigurator->registerCustomMessageDestinations(
        EmitSchedulerOperation::class,
        new Destination('scheduler', (string) \getenv('TRANSPORT_ROUTING_KEY'))
    ); 
```
В примере мы создаём exchange с типом ```x-delayed-message``` под названием ```scheduler``` (вы можете назвать его как угодно), затем добавляем маршрут для сообщений в наш основной exchange.
И указывает маршрут для события о добавлении нового задания планировщика (данное сообщение обрабатывается фреймворком автоматически, подписывать на него нельзя).

#### Использование
Пример отложенного выполнения:

```php
    /**
     * @CommandHandler()
     *
     * @param RegisterCustomer  $command
     * @param KernelContext     $context
     * @param SchedulerProvider $provider
     *
     * @return \Generator<null>
     */
    public function someAction(
        RegisterCustomer $command,
        KernelContext $context,
        SchedulerProvider $provider
    ): \Generator
    {
        $delayedOperationId = new ScheduledOperationId(uuid());

        yield $provider->schedule(
            $delayedOperationId,
            new SomeDelayedCommand(/** payload */),
            datetimeInstantiator('+2 minutes'),
            $context
        );

        /** .... */
    }
````
В данном примере команда ```SomeDelayedCommand``` будет выполнена через 2 минуты.

#### Отмена команды
Если по каким-либо причинам вам необходимо отменить выполнение запланированной команды, то нужно вызвать метод [cancel()]() класса [SchedulerProvider](https://github.com/mmasiukevich/service-bus/blob/master/src/SchedulerProvider.php), передав в него идентификатор, с которым задание было создано (в примере выше это ```$delayedOperationId```)
