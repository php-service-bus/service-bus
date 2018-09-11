[![Build Status](https://travis-ci.org/mmasiukevich/service-bus.svg?branch=master)](https://travis-ci.org/mmasiukevich/service-bus)
[![Coverage Status](https://coveralls.io/repos/github/mmasiukevich/service-bus/badge.svg?branch=master)](https://coveralls.io/github/mmasiukevich/service-bus?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mmasiukevich/service-bus/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mmasiukevich/service-bus/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/mmasiukevich/service-bus/v/stable)](https://packagist.org/packages/mmasiukevich/service-bus)
[![Latest Unstable Version](https://poser.pugx.org/mmasiukevich/service-bus/v/unstable)](https://packagist.org/packages/mmasiukevich/service-bus)
[![Total Downloads](https://poser.pugx.org/mmasiukevich/service-bus/downloads)](https://packagist.org/packages/mmasiukevich/service-bus)
[![License](https://poser.pugx.org/mmasiukevich/service-bus/license)](https://packagist.org/packages/mmasiukevich/service-bus)

## What is it?
Фреймворк, позволяющий реализовать асинхронный обмен сообщениями, прозрачный workflow, а так же контроль долгоживущих бизнесс процессов благодаря применению паттерна Saga. 
Основан на **[message based architecture](https://www.enterpriseintegrationpatterns.com/patterns/messaging/Messaging.html)** и включает реализацию следующих паттернов: Saga, CQRS, Publish\subscribe, Message bus

#### Scope of use
Главным образом подходит для реализации распределённых систем. Благодаря применению шины сообщений и паттерна Saga позволяет если и не убрать полностью, то по крайней мере серьёзно уменьшить связь отдельных контекстов

#### Main Features
 - Асинхронное выполнение сообщений
 - Распределённость (Сообщения могут обрабатываться разными процессами)
   - Подписчики могут быть написаны на любом языке программирования
 - Аркестрация долгоживущих бизнесс процессов (например, выполнение процесса оформления заказа в интернет магазине) с помощью [Saga Pattern](https://github.com/mmasiukevich/service-bus/blob/develop/doc/sagas.md)
 - Полная история изменения агрегата благодаря применению [EventSourcing](https://github.com/mmasiukevich/service-bus/blob/develop/doc/event_sourcing.md)
 - Уменьшение связанности между компонентами (контекстами) приложения

#### Documentation 
- [EventSourcing](https://github.com/mmasiukevich/service-bus/blob/develop/doc/event_sourcing.md)
- [Sagas](https://github.com/mmasiukevich/service-bus/blob/develop/doc/sagas.md)
- [Messages (Command/Event/Query)](https://github.com/mmasiukevich/service-bus/blob/develop/doc/messages.md)
- [Initialization](https://github.com/mmasiukevich/service-bus/blob/develop/doc/initialization.md)

#### Requirements 
  - PHP 7.2

## Security

If you discover any security related issues, please email [`desperado@minsk-info.ru`](mailto:desperado@minsk-info.ru) instead of using the issue tracker.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.