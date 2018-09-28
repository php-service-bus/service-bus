[![Build Status](https://travis-ci.org/mmasiukevich/service-bus.svg?branch=master)](https://travis-ci.org/mmasiukevich/service-bus)
[![Coverage Status](https://coveralls.io/repos/github/mmasiukevich/service-bus/badge.svg?branch=master)](https://coveralls.io/github/mmasiukevich/service-bus?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mmasiukevich/service-bus/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mmasiukevich/service-bus/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/mmasiukevich/service-bus/v/stable)](https://packagist.org/packages/mmasiukevich/service-bus)
[![Latest Unstable Version](https://poser.pugx.org/mmasiukevich/service-bus/v/unstable)](https://packagist.org/packages/mmasiukevich/service-bus)
[![Total Downloads](https://poser.pugx.org/mmasiukevich/service-bus/downloads)](https://packagist.org/packages/mmasiukevich/service-bus)
[![License](https://poser.pugx.org/mmasiukevich/service-bus/license)](https://packagist.org/packages/mmasiukevich/service-bus)

## What is it?
A concurrency (based on [Amp](https://github.com/amphp)) framework, that lets you implement an asynchronous messaging, a transparent workflow and control of long-lived business transactions by means of the Saga pattern. It implements the [message based architecture](https://www.enterpriseintegrationpatterns.com/patterns/messaging/Messaging.html) and it includes the following patterns: Saga, CQRS, Publish\Subscribe, Message Bus.

#### Scope of use
Basically, it is suitable for development of distributed applications. By using the Message Bus and Saga pattern it lets you decrease the coupling of contexts.

#### Main Features
 - Asynchronous messaging
 - Distribution (messages can be handled by different processes).
   - Subscribers can be implemented on any programming language.
 - Orchestration of long-lived business transactions (for example, a checkout) with the help of [Saga Pattern](https://github.com/mmasiukevich/service-bus/blob/master/doc/sagas.md)
 - Full history of aggregate changes ([EventSourcing](https://github.com/mmasiukevich/service-bus/blob/master/doc/event_sourcing.md))
 - Decrease of the coupling between the components (contexts)

#### Documentation
- [EventSourcing](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_event_sourcing.md)
- [Sagas](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_sagas.md)
- [Messages (Command/Event/Query)](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_messages.md)
- [Processing of messages](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_massage_handlers.md)
- [Scheduler](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_scheduler.md)
- [Database adapters](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_storages.md)
- [Initialization](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_initialization.md)

[Russian version](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_readme.md)

#### Requirements
  - PHP 7.2
  - RabbitMQ (You can implement  [Transport](https://github.com/mmasiukevich/service-bus/blob/master/src/Transport/Transport.php) interface)
  - PostgreSQL [Learn more about adapters](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_storages.md)


## Security

If you discover any security related issues, please email [`desperado@minsk-info.ru`](mailto:desperado@minsk-info.ru) instead of using the issue tracker.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.

## Known Issues

