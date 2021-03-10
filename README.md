[![Packagist](https://img.shields.io/packagist/v/php-service-bus/service-bus.svg)](https://packagist.org/packages/php-service-bus/service-bus)
[![Packagist](https://img.shields.io/packagist/dt/php-service-bus/service-bus.svg)](https://packagist.org/packages/php-service-bus/service-bus)
![Continuous Integration](https://github.com/php-service-bus/service-bus/workflows/Continuous%20Integration/badge.svg)
[![codecov](https://codecov.io/gh/php-service-bus/service-bus/branch/v5.0/graph/badge.svg?token=0bKwdiuo0S)](https://codecov.io/gh/php-service-bus/service-bus)
[![Shepherd](https://shepherd.dev/github/php-service-bus/service-bus/coverage.svg)](https://shepherd.dev/github/php-service-bus/service-bus)
[![Latest Stable Version](https://poser.pugx.org/php-service-bus/service-bus/v/stable)](https://packagist.org/packages/php-service-bus/service-bus)
[![License](https://poser.pugx.org/php-service-bus/service-bus/license)](https://packagist.org/packages/php-service-bus/service-bus)
[![Financial Contributors on Open Collective](https://opencollective.com/php-service-bus/all/badge.svg?label=financial+contributors)](https://opencollective.com/php-service-bus) 

### Introduction
A concurrency (based on [Amp](https://github.com/amphp)) framework, that lets you implement an asynchronous messaging, a transparent workflow and control of long-lived business transactions by means of the Saga pattern. It implements the [message based architecture](https://www.enterpriseintegrationpatterns.com/patterns/messaging/Messaging.html) and it includes the following patterns: Saga, Publish\Subscribe, Message Bus.

### Main Features
 - Сooperative multitasking
 - Asynchronous messaging (Publish\Subscribe pattern implementation)
 - Event-driven architecture
 - Distribution (messages can be handled by different applications)
   - Subscribers can be implemented on any programming language
 - [High performance](https://github.com/php-service-bus/performance-comparison)
 - Orchestration of long-lived business transactions (for example, a checkout) with the help of Saga Pattern
 - Full history of aggregate changes (EventSourcing)

### See it in action
Jump into our [Quick Start](.documentation/quick_start.md) and build your first distributed solution in just 15 minutes.

### Documentation

Documentation can be found in the `.documentation` directory

* [Configuration](.documentation/configuration.md)
* [Transport](.documentation/transport.md)
* [Storage](.documentation/database.md)
* [Sagas](.documentation/sagas.md)
* [EventSourcing](.documentation/event_sourcing.md)
* [HttpClient](.documentation/http_client.md)
* [Scheduler](.documentation/scheduler.md)
* [Cache](.documentation/cache.md)
* [Mutex](.documentation/mutex.md)

### Requirements
  - PHP >=8.0
  - RabbitMQ/Redis/Nsq
  - PostgreSQL

Contributions are welcome! Please read [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

### Communication Channels
You can find help and discussion in the following places:
* [Telegram chat (RU)](https://t.me/php_service_bus)
* [Twitter](https://twitter.com/PhpBus)
* Create issue [https://github.com/php-service-bus/service-bus/issues](https://github.com/php-service-bus/service-bus/issues)

## Contributing
Contributions are welcome! Please read [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

### License
The MIT License (MIT). Please see [LICENSE](./LICENSE.md) for more information.