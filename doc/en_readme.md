## What is this?
This is a framework allowing to implement asynchronous messaging with clear workflow using Saga pattern. This pattern also allows to greatly improve control over long-lasting business processes.
Framework is based on [message based architecture](https://www.enterpriseintegrationpatterns.com/patterns/messaging/Messaging.html) and implements Saga pattern via Publish-Subscribe message bus following CQRS.

#### Field of use
Mostly useful for implementation of distributed systems. Saga pattern allows to noticeably (if not completely) reduce coupling between different contexts.

#### Main features
- Asynchronous messaging
- Distributed architecture (messages may be handled by different processes written in different languages)
- Orchestration of long-living business processes (like making an order in online shop) using [Saga Pattern](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_sagas.md)
- Full history of aggregate changes using [Event Sourcing](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_event_sourcing.md)
- Reduction of coupling between different components (contexts) of the application

#### Documentation
- [EventSourcing](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_event_sourcing.md)
- [Sagas](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_sagas.md)
- [Messages (Command/Event/Query)](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_messages.md)
- [Processing of messages](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_message_handlers.md)
- [Scheduler](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_scheduler.md)
- [Database adapters](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_storages.md)
- [Initialization](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_initialization.md)

#### Requirements
- PHP 7.2
- RabbitMQ (using other transport layer is possible with custom implementation of [Transport](https://github.com/mmasiukevich/service-bus/blob/master/src/Transport/Transport.php) interface)
- PostgreSQL [more about adapters](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_storages.md)

## Security

If you found any security-related bug, please mail [`desperado@minsk-info.ru`](mailto:desperado@minsk-info.ru) instead of using bug tracker.

## License

[LICENSE](../LICENSE.md)

## Known issues
