## Table of contents
* [Что такое саги](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%A7%D1%82%D0%BE-%D1%82%D0%B0%D0%BA%D0%BE%D0%B5-%D1%81%D0%B0%D0%B3%D0%B8)
* [Область применения](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%9E%D0%B1%D0%BB%D0%B0%D1%81%D1%82%D1%8C-%D0%BF%D1%80%D0%B8%D0%BC%D0%B5%D0%BD%D0%B5%D0%BD%D0%B8%D1%8F)
* [Особенности реализации](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%9E%D1%81%D0%BE%D0%B1%D0%B5%D0%BD%D0%BD%D0%BE%D1%81%D1%82%D0%B8-%D1%80%D0%B5%D0%B0%D0%BB%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D0%B8)
* [Минусы при использовании](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%9C%D0%B8%D0%BD%D1%83%D1%81%D1%8B-%D0%BF%D1%80%D0%B8-%D0%B8%D1%81%D0%BF%D0%BE%D0%BB%D1%8C%D0%B7%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B8)
* [Конфигурация](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%9A%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B0%D1%86%D0%B8%D1%8F)
* [Жизненный цикл](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%96%D0%B8%D0%B7%D0%BD%D0%B5%D0%BD%D0%BD%D1%8B%D0%B9-%D1%86%D0%B8%D0%BA%D0%BB)
* [Создание](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%A1%D0%BE%D0%B7%D0%B4%D0%B0%D0%BD%D0%B8%D0%B5)
* [Примеры кода](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B-%D0%BA%D0%BE%D0%B4%D0%B0)

#### What is Saga?
Saga may be interpreted as any documented business process which consists of steps. Speaking technically, Saga is an Event Listener which listens to some [event](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_messages.md#event) and performs an action based on that event. A good example is a flowchart with a decision symbol.

There are synchronous, asynchronous and mixed sagas (where some steps may be performed synchronously and some asynchronously). From personal experience only asynchronous sagas are worth implementing.

A little bit more on [Saga](https://microservices.io/patterns/data/saga.html).

#### Field of use
* Description of complicated business processes. A good example is electronic document management. Transfer of a document may take some time - up to weeks depending on many factors. The process itself consists of a dozen of steps including electronic signatures of 3 parties (3 party is the operator)
* Distributed transactions (emulated Atomicity): either each step would be finished successfully or a step-specific compensating action will be performed.

#### Implementation features
All the sagas in the framework are asynchronous and have their own state which is serialized and stored in database. Any variables expect closures may be used as saga state.
Any non-closed saga may be triggered starting from any of saga's steps if a corresponding event is received.

#### Caveats
The more sagas (and steps within them) you have the more documentation you need for them. Single saga may trigger other sagas heavily increasing complexity of business processes.
[Message based architecture](https://www.enterpriseintegrationpatterns.com/patterns/messaging/Messaging.html) is very difficult to understand at the beginning (especially after "common" PHP programming) and requires responsibility.
