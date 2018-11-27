Оглавление
* [Что такое саги](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%A7%D1%82%D0%BE-%D1%82%D0%B0%D0%BA%D0%BE%D0%B5-%D1%81%D0%B0%D0%B3%D0%B8)
* [Область применения](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%9E%D0%B1%D0%BB%D0%B0%D1%81%D1%82%D1%8C-%D0%BF%D1%80%D0%B8%D0%BC%D0%B5%D0%BD%D0%B5%D0%BD%D0%B8%D1%8F)
* [Особенности реализации](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%9E%D1%81%D0%BE%D0%B1%D0%B5%D0%BD%D0%BD%D0%BE%D1%81%D1%82%D0%B8-%D1%80%D0%B5%D0%B0%D0%BB%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D0%B8)
* [Минусы при использовании](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%9C%D0%B8%D0%BD%D1%83%D1%81%D1%8B-%D0%BF%D1%80%D0%B8-%D0%B8%D1%81%D0%BF%D0%BE%D0%BB%D1%8C%D0%B7%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B8)
* [Конфигурация](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%9A%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B0%D1%86%D0%B8%D1%8F)
* [Жизненный цикл](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%96%D0%B8%D0%B7%D0%BD%D0%B5%D0%BD%D0%BD%D1%8B%D0%B9-%D1%86%D0%B8%D0%BA%D0%BB)
* [Создание](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%A1%D0%BE%D0%B7%D0%B4%D0%B0%D0%BD%D0%B8%D0%B5)
* [Примеры кода](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_sagas.md#%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B-%D0%BA%D0%BE%D0%B4%D0%B0)

#### Что такое саги
Какой-либо описанный бизнесс процесс. Если провести аналогию на что-то попроще, то сага - это Event Listener. Она ждёт какое-либо [событие](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_messages.md#%D0%A1%D0%BE%D0%B1%D1%8B%D1%82%D0%B8%D1%8F-event) и на основании него выполняет действие.
Самый яркий пример - блок-схема: есть условие, есть действие.

Саги бывают синхронные, асинхронные и смешанные (в которых часть шагов может выполняться синхронно, а часть асинхронно). По личному опыту, только асинхронный вариант является хоть сколько-нибудь оправданным.

Более подробное описание: [Pattern: Saga](https://microservices.io/patterns/data/saga.html)
#### Область применения
* Описание сложных процессов. Хороший пример - электронный документооборот. Передача документа от одной компании к другой может занимать минуты, дни, недели... Сам процесс состоит из десятка пунктов, включающих 3 электронные подписи сторон (3-я сторона - оператор)
* Распределённые транзакции (эмулирует Atomicity): либо успешно завершаются все шаги, либо будет выполнено какое-либо компенсирующее действие, которое отменит изменения.

#### Особенности реализации
В предложенной имплементации саги всегда асинхронные и имеют состояние. Оно хранится в базе данных в сериализованном виде. Вы можете использовать любые переменные (за исключением переменных, содержащих замыкания).
Любую незакрытую сагу можно запустить с любого из её шагов, отправив в транспорт соответствующее шагу событие.

#### Минусы при использовании
С ростом количества саг (ровно как и шагов в них) возрастает требования к документации всего процесса. Одна сага может запускать другие, что ещё сильнее увеличивает сложность понимания.
[Message based architecture](https://www.enterpriseintegrationpatterns.com/patterns/messaging/Messaging.html) непривычна в начале и требует довольно ответственного подхода.


#### Конфигурация
Конфигурация саг указывается в аннотациях [@SagaHeader](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Annotations/SagaHeader.php) и [@SagaEventListener](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Annotations/SagaEventListener.php) соответсвенно
 - ```idClass```: Пространство имён класса идентификатора саги.
 - ```expireDateModifier```: Интервал времени, в течение которого сага будет считаться открытой
 - ```containingIdProperty```: Поле, в которм будет передаваться идентификатор саги. В нашем примере это поле называется *operationId*. Для того, чтобы привязать событие к конкретной саге (ведь одно и то же событие может ждать несколько саг),  нужно передавать идентификатор. Данная опция указывает на то, какое свойство события содержит этот идентификатор. Параметр можно переопределить в рамках конкретного слушателя, указав в аннотации @SagaEventListener нужное значение.

Каждый слушатель именуется по следующему шаблону: ```on{EventName}```, где *on* - префикс, а *{EventName}* базовое название класса (без указания пространства имён)

#### Жизненный цикл
Выполнение саги начинается с команды [start()](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Saga.php#L133), которая будет вызвана автоматически (пример создания ниже). В рамках саги доступны методы (имеют protected область видимости):
- [fire()](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Saga.php#L195): Отправляет в транспорт команду
- [raise()](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Saga.php#L178): Отправляет в транспорт событие
- [makeCompleted()](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Saga.php#L213): Закрывает сагу, помечая её как успешную
- [makeFailed()](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Saga.php#L232): Завершает сагу, помечая её как неуспешную

При изменениях статуса в транспорт будут отправлены события:
- [SagaCreated()](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Contract/SagaCreated.php): Сага была создана
- [SagaStatusChanged()](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Contract/SagaStatusChanged.php): Изменился статус саги
- [SagaClosed()](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Contract/SagaClosed.php): Обработка саги завершена

#### Создание
Для операций с сагами есть специальный провайдер - [SagaProvider](https://github.com/mmasiukevich/service-bus/blob/master/src/SagaProvider.php), имеющий несколько методов (каждый метод возвращает объект [Promise](https://github.com/amphp/amp/blob/master/lib/Promise.php))
- [start()](https://github.com/mmasiukevich/service-bus/blob/master/src/SagaProvider.php#L88): Создаёт и запускает новую сагу, передавая в неё команду
- [obtain()](https://github.com/mmasiukevich/service-bus/blob/master/src/SagaProvider.php#L141): Получает существующую сагу из базы данных
- [save()](https://github.com/mmasiukevich/service-bus/blob/master/src/SagaProvider.php#L200): Сохраняет все изменения в саге, а также триггерит отправку сообщений в транспорт

#### Примеры кода

```php
<?php

declare(strict_types = 1);

namespace Desperado\ServiceBus;

use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Sagas\Annotations\SagaEventListener;
use Desperado\ServiceBus\Sagas\Annotations\SagaHeader;
use Desperado\ServiceBus\Sagas\Saga;
use Desperado\ServiceBus\Sagas\SagaId;

/**
 * @SagaHeader(
 *     idClass="Desperado\ServiceBus\ExampleSagaId",
 *     expireDateModifier="+1 year",
 *     containingIdProperty="operationId"
 * )
 */
final class ExampleSaga extends Saga
{
    /**
     * @inheritDoc
     */
    public function start(Command $command): void
    {
        $this->fire(
            new SomeCommand(/** params */)
        );
    }

    /**
     * @SagaEventListener()
     *
     * @param SomeEvent $event
     *
     * @return void
     */
    private function onSomeEvent(SomeEvent $event): void
    {
        $this->fire(
            new NextCommand(/**  */)
        );
    }
}

final class ExampleSagaId extends SagaId
{

}
```

```
$sagaProvider = new SagaProvider(
    new SQLSagaStore(
        StorageAdapterFactory::inMemory()
    )
);

$promise = $sagaProvider->start(
    ExampleSagaId::new(ExampleSaga::class),
    new SomeStartCommand(),
    new KernelContext()
);
```
При старте саги будет выполнен запуск саги, а также сохранение. Если после старта вы никак не влияли на стейт саги, то вызывать метод save не обязательно. В ином случае, нужно. Если сага была загружена из базы данных, то в конце работы с ней нужно будет вызывать метод save.

*Уникальность саги обеспечивается при помощи составного ключа, который включает в себя uuid и пространство имён класса идентификатора. Уникальности uuid в принципе достаточно, но для удобства нередко возникает необходимость запустить несколько разных саг (разного типа) в одним идентификатором*
