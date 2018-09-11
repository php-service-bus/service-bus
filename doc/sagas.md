### Пример

```$php
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

Конфигурация саг указывается в аннотациях [@SagaHeader](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Annotations/SagaHeader.php) и [@SagaEventListener()](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Annotations/SagaEventListener.php) соответсвенно
 - **idClass**: Пространство имён класса идентификатора саги.
 - **expireDateModifier**: Интервал времени, в течение которого сага будет считаться открытой
 - **containingIdProperty**: Поле, в которм будет передаваться идентификатор саги. В нашем примере это поле называется *operationId*. Для того, что бы привязать событие к конкретной саге (ведь одно и то же событие может ждать несколько саг),  нужно передавать идентификатор. Данная опция указывает на то, какое свойство события содержит этот идентификатор. Параметр можно переопределить в рамках конкретного слушателя, указав в аннотации @SagaEventListener нужное значение.
 
Каждый слушатель именуется по следующему шаблону: ```on{EventName}```, где *on* - префикс, а *{EventName}* базовое название класса (без указания пространства имён)

Выполнение саги начинается с команды [start](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Saga.php#L133), которая будет вызвана автоматически (пример создания ниже). В рамках саги доступны методы (имеют protected область видимости):
- [fire](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Saga.php#L191): Отправляет в транспорт команду
- [raise](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Saga.php#L174): Отправляет в транспорт событие
- [makeCompleted](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Saga.php#L209): Закрывает сагу, помечая её, как успешную
- [makeFailed](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Saga.php#L228): Завершает сагу, помечая её, как неуспешную

При изменениях статуса в транспорт будут отправлены события:
- [SagaCreated](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Contract/SagaCreated.php): Сага была создана
- [SagaStatusChanged](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Contract/SagaStatusChanged.php): Изменился статус саги
- [SagaClosed](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/Contract/SagaClosed.php): Обработка саги завершена

#### Создание
Для операций с сагами есть специальный провайдер - [SagaProvider](https://github.com/mmasiukevich/service-bus/blob/master/src/SagaProvider.php), имеющий несколько методов (каждый метод возвращает объект Promise)
- [start](https://github.com/mmasiukevich/service-bus/blob/master/src/SagaProvider.php#L78): Создаёт и запускает новую сагу, передавая в неё команду 
- [obtain](https://github.com/mmasiukevich/service-bus/blob/master/src/SagaProvider.php#L126): Получает существующую сагу из базы данных
- [save](https://github.com/mmasiukevich/service-bus/blob/master/src/SagaProvider.php#L161): Сохраняет все изменения в саге, а так же триггерит отправку сообщений в транспорт
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
При старте саги будет выполнен запуск саги, а так же сохранение. Еслли после старта вы никак не влияли на стейт саги, то вызывать метод save не обязательно. В ином случае, нужно. Если сага была загружена из базы данных, то в конце работы с ней нужно будет вызывать метод save.

*Уникальность саги обеспечивается при помощи составного ключа, который включает в себя uuid и пространство имён класса идентификатора. Уникальности uuid в принципе достаточно, но для удобства нередко возникает необходимость запустить несколько разных саг (разного типа) в одним идентификатором*