Оглавление
* [Особенности реализации](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_scheduler.md#%D0%9E%D1%81%D0%BE%D0%B1%D0%B5%D0%BD%D0%BD%D0%BE%D1%81%D1%82%D0%B8-%D1%80%D0%B5%D0%B0%D0%BB%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D0%B8)
* [Конфигурация](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_scheduler.md#%D0%9A%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B0%D1%86%D0%B8%D1%8F)
* [Использование](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_scheduler.md#%D0%98%D1%81%D0%BF%D0%BE%D0%BB%D1%8C%D0%B7%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5)
* [Отмена команды](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_scheduler.md#%D0%9E%D1%82%D0%BC%D0%B5%D0%BD%D0%B0-%D0%BA%D0%BE%D0%BC%D0%B0%D0%BD%D0%B4%D1%8B)

#### Особенности реализации
На данный момент планировщик работает **только** с [RabbitMQ](https://www.rabbitmq.com/) с установленным [rabbitmq-delayed-message-exchange](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange) плагином.

#### Конфигурация
Во время [инициализации демона](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_initialization.md) у объекта класс [Bootstrap](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php) необходимо вызвать метод [enableScheduler()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L95). 
Данный метод регистрирует сервисы, необходимые для работы планировщика, а так же добавляет 4 обработчика для сообщений.

Помимо всего прочего необходимо дополнительно сконфигурировать транспортный уровень:

```php
        yield $transport->createTopic(
            AmqpExchange::delayed((string) \getenv('SCHEDULER_TOPIC')),
            new TopicBind(
                $mainExchange,
                \getenv('TRANSPORT_ROUTING_KEY')
            )
        );

        $kernel->registerMessageCustomEndpoint(
            EmitSchedulerOperation::class,
            new ApplicationTransportEndpoint(
                $transport,
                new AmqpTransportLevelDestination(
                    (string) \getenv('SCHEDULER_TOPIC'),
                    \getenv('TRANSPORT_ROUTING_KEY')
                )
            )
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
Если по каким-либо причинам вам необходимо отменить выполнение запланированной команды, то нужно вызвать метод [cancel()](https://github.com/mmasiukevich/service-bus/blob/master/src/SchedulerProvider.php#L141) класса [SchedulerProvider](https://github.com/mmasiukevich/service-bus/blob/master/src/SchedulerProvider.php), передав в него идентификатор, с которым задание было создано (в примере выше это ```$delayedOperationId```)
