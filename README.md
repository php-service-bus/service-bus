[![Build Status](https://travis-ci.org/mmasiukevich/service-bus.svg?branch=master)](https://travis-ci.org/mmasiukevich/service-bus)
[![Coverage Status](https://coveralls.io/repos/github/mmasiukevich/service-bus/badge.svg?branch=master)](https://coveralls.io/github/mmasiukevich/service-bus?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mmasiukevich/service-bus/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mmasiukevich/service-bus/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/mmasiukevich/service-bus/v/stable)](https://packagist.org/packages/mmasiukevich/service-bus)
[![Latest Unstable Version](https://poser.pugx.org/mmasiukevich/service-bus/v/unstable)](https://packagist.org/packages/mmasiukevich/service-bus)
[![Total Downloads](https://poser.pugx.org/mmasiukevich/service-bus/downloads)](https://packagist.org/packages/mmasiukevich/service-bus)
[![License](https://poser.pugx.org/mmasiukevich/service-bus/license)](https://packagist.org/packages/mmasiukevich/service-bus)

#   What is it?
Фреймворк, позволяющий реализовать асинхронный обмен сообщениями, прозрачный workflow, а так же контроль долгоживущих бизнесс процессов благодаря применению паттерна Saga. 
Основан на **[message based architecture](https://www.enterpriseintegrationpatterns.com/patterns/messaging/Messaging.html)** и включает реализацию следующих паттернов:
  - Publish\subscribe
  - Command bus
  - Event bus
  - [Saga](https://microservices.io/patterns/data/saga.html)
  - [Event sourcing](https://microservices.io/patterns/data/event-sourcing.html)
  - [CQRS](https://microservices.io/patterns/data/cqrs.html)

Все типы сообщений делятся на команды и события. Команда - указание на необходимость выполнить какое-либо действие. Событие - отражение того, что произошло в системе (например, следствие действия). Обработчик для команды всегда 1, в то время как для события может быть множество подписчиков.
В качестве транспорта для сообщений в данный момент реализован только [RabbitMQ](https://www.rabbitmq.com/)

# Scope of use
Главным образом подходит для реализации распределённых систем. Благодаря применению шины сообщений и паттерна Saga позволяет если и не убрать полностью, то по крайней мере серьёзно уменьшить связь отдельных контекстов.

# Main Features
 - Асинхронное выполнение сообщений (может быть запущено несколько процессов)
 - Аркестрация долгоживущих бизнесс процессов (например, выполнение процесса оформления заказа в интернет магазине)
 - Полная история изменения агрегата благодаря применению event sourcing
 - Уменьшение связанности между компонентами (контекстами) приложения

# Components 
Фреймворк основан на компонентах Symfony 4.x (на текущий момент 4.1)
- **Transport**: абстракция над транспортным слоем (на текущий момент только RabbitMQ)
- **Storage**: абстракция для работы с хранилищем. 
  - На текущий момент реализована работа только с пулом соединений **PostgreSQL**. Для тестирования доступен адаптер для работы с **PDO** (на базе **DoctrineDBAL**), но использовать его крайне не рекомендуется.
- **Sagas**
- **EventSourcing**
- **EventSourcingSnapshots**: поддержка снимков агрегатов. Необходимо для того, что бы сократить время и ресурсы на восстановление состояния агрегата.
- **Index**: Используется в основном в рамках **Event sourcing**.
  - При использовании **Event sourcing** зачастую возникают трудности фильтрации данных. Дело в том, что у нас не хранится конечное состояние агрегата, по которому можно что-либо искать. У нас хранится список событий над и конечное состояние можно получить, наложив все события по очереди друг на друга. Компонент позволяет задать связь между каким-то значением (например, email пользователя) и конкретным агрегатом (идентификатором)
- **Marshal**: Нормализация\денормализация, а так же сериализация\десериализация объектов сообщений. На текущий момент используется **SymfonySerializer**, который кодирует сообщения в json и обратно.
- **MessageBus**: Шина сообщений. В ней регистрируются все обработчики и слушатели. Служит своего рода роутером для сообщений.
- **HttpClient**: Неблокирующий http клиент
