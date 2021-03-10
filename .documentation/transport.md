#### Available implementations
* [RabbitMqTransport](https://github.com/php-service-bus/transport/blob/v5.0/src/Amqp/PhpInnacle/PhpInnacleTransport.php)
* [RedisTransport](https://github.com/php-service-bus/transport/blob/v5.0/src/Redis/RedisTransport.php)
* [NsqTransport](https://github.com/php-service-bus/transport/blob/v5.0/src/Nsq/NsqTransport.php)

#### Installation
```
composer req php-service-bus/transport
```

#### RabbitMQ

```php
$transportModule = new PhpInnacleTransportModule($connectionDSN, $defaultDestinationExchange, $defaultDestinationRoutingKey);
$transportModule->configureQos($qosSize, $qosCount, $isGlobal);

$bootstrap->applyModules($transportModule);
```
* `connectionDSN`: RabbitMQ connection DSN. Example: `amqp://guest:guest@localhost:5672?vhost=/&heartbeat=60&timeout=1`;
* `defaultDestinationExchange`: The exchange to which messages will be sent by default;
* `defaultDestinationRoutingKey`: Routing key with which messages will be sent by default;
* `qosSize`: Prefetching size;
* `qosCount`: Prefetching count;
* `isGlobal`: Configure qos for all channels/for current channel only.

#### Redis

```php
$bootstrap->applyModules(
  new RedisTransportModule($connectionDSN, $defaultDestinationChannel)
);
```
* `connectionDSN`:  Redis connection DSN. Example: `tcp://test-host:6379?timeout=10&password=qwerty`;
* `defaultDestinationChannel`: The channel to which messages will be sent by default.

#### Nsq

```php
$bootstrap->applyModules(
    new NsqTransportModule($connectionDSN, $defaultDestinationChannel)
);
```
* `connectionDSN`: NSQ connection DSN. Example: `tcp://localhost:4150`
* `defaultDestinationChannel`: The channel to which messages will be sent by default.