## Что это такое?
Фреймворк, позволяющий реализовать асинхронный обмен сообщениями, прозрачный workflow, а так же контроль долгоживущих бизнесс процессов благодаря применению паттерна Saga.
Основан на [message based architecture](https://www.enterpriseintegrationpatterns.com/patterns/messaging/Messaging.html) и включает реализацию следующих паттернов: Saga, CQRS, Publish\Subscribe, Message Bus

#### Область применения
Главным образом подходит для реализации распределённых систем. Благодаря применению шины сообщений и паттерна Saga позволяет если и не убрать полностью, то по крайней мере серьёзно уменьшить связь отдельных контекстов

#### Основные возможности
 - Асинхронное выполнение сообщений
 - Распределённость (сообщения могут обрабатываться разными процессами)
   - Подписчики могут быть написаны на любом языке программирования
 - Аркестрация долгоживущих бизнесс процессов (например, выполнение процесса оформления заказа в интернет магазине) с помощью [Saga Pattern](https://github.com/mmasiukevich/service-bus/blob/master/doc/sagas.md)
 - Полная история изменения агрегата благодаря применению [EventSourcing](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md)
 - Уменьшение связанности между компонентами (контекстами) приложения

#### Документация
- [EventSourcing](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md)
- [Sagas](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md)
- [Messages (Command/Event/Query)](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_messages.md)
- [Processing of messages](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_massage_handlers.md)
- [Scheduler](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_scheduler.md)
- [Database adapters](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_storages.md)
- [Initialization](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_initialization.md)

#### Требования
  - PHP 7.2
  - RabbitMQ (можно использовать свой транспорт, реализовав  [Transport](https://github.com/mmasiukevich/service-bus/blob/master/src/Transport/Transport.php))
  - PostgreSQL [Подробнее про адаптеры](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_storages.md)


## Безопасность

If you discover any security related issues, please email [`desperado@minsk-info.ru`](mailto:desperado@minsk-info.ru) instead of using the issue tracker.

## Лицензия

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.