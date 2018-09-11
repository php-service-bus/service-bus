Оглавление
* [Введение]()
* [Конфигурация]()
* [Обработчик команд]()
* [Обработчик событий]()
* [Argument Resolver]()
* [Валидация]()
* [Пример кода]()

#### Введение
Все обработчики представлены в виде публичных методов класса, у которых есть 2 обязательных аргумента (идущих первыми):
* Объект сообщения (Реализующего интерфейс [Command](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/Contract/Messages/Command.php) или [Event](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/Contract/Messages/Event.php)). [Подробнее о сообщениях](https://github.com/mmasiukevich/service-bus/blob/master/doc/messages.md)
* Объект [KernelContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php). [Побробнее про контекст](https://github.com/mmasiukevich/service-bus/blob/master/doc/context.md)

Методы могут возвращать void, \Generator и [Promise](https://github.com/amphp/amp/blob/master/lib/Promise.php)
Название методов с точки зрения инфраструктуры значения не имеет.
В классе могут содержаться вспомогательные методы, которые не помечены, как обработчики

#### Конфигурация
В [примере инициализации](https://github.com/mmasiukevich/service-bus/blob/master/doc/initialization.md) указан вариант автоматической регистрации всех обработчиков. Если по каким-либо причинам автоматическая регистрация не подходит, вы можете описать класс в виде сервиса, добавив ему тег ```service_bus.service``` (без значения)

#### Обработчик команд
Обработчик команд выделяется аннотацией [CommandHandler](https://github.com/mmasiukevich/service-bus/blob/master/src/Services/Annotations/CommandHandler.php)

#### Обработчик событий
Подписчик выделяется аннотацией [EventListener](https://github.com/mmasiukevich/service-bus/blob/master/src/Services/Annotations/EventListener.php)

#### Argument Resolver
Во фреймворке реализовано автоматическая передача зависимостей в метод обработчика. Объект сообщения и контекста, как уже говорилось выше, присутствует всегда. Помимо него в качестве аргумента можно указать любой из зарегистрированных сервисов.

Например:
```php
    public function renameCustomer(
        RenameCustomerCommand $command,
        KernelContext $context,
        SagaProvider $sagaProvider
    ): \Generator {/** ... */}
```
Можно подставить произвольное кол-во зависимостей. Всё ограничено лишь здравым смыслом. Возможно, часть зависимостей лучше вынести в конструктор и сконфигурировать самостоятельно. Впрочем, этот вопрос выходит за рамки описания возможностей фреймворка.

#### Валидация
Для обработчиков команд и событий можно включить валидацию, если это необходимо. Правила валидации описываются аннотациями в классе сообщения с помощью аннотаций. Подробнее о [SymfonyValidation](https://symfony.com/doc/current/validation.html)

#### Пример кода
```php
<?php

declare(strict_types = 1);

namespace DocumentProcessing\TransferTransaction;

use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Services\Annotations\CommandHandler;
use Desperado\ServiceBus\Services\Annotations\EventListener;

final class TestService
{
    /**
     * @CommandHandler(
     *     validate=true
     * )
     *
     * @param Register      $command
     * @param KernelContext $context
     * @param SagaProvider  $sagaProvider
     *
     * @return \Generator
     */
    public function handleUserRegister(
        Register $command,
        KernelContext $context,
        SagaProvider $sagaProvider
    ): \Generator
    {
        try
        {
            yield $sagaProvider->start(
                UserRegistrationSagaId::new(UserRegistrationSaga::class),
                $command,
                $context
            );
        }
        catch(\Throwable $throwable)
        {
            $context->logContextThrowable($throwable);

            yield $context->delivery(
                new RegistrationFailed($throwable->getMessage())
            );
        }
    }

    /**
     * @EventListener()
     *
     * @param RegistrationFailed $event
     * @param KernelContext      $context
     *
     * @return void
     */
    public function whenRegistrationFailed(RegistrationFailed $event, KernelContext $context): void
    {
        $context->logContextMessage('Shit happens...', ['reason' => $event->message]);
    }
}

```