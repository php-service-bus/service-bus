Оглавление
* [Введение](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_message_handlers.md#%D0%92%D0%B2%D0%B5%D0%B4%D0%B5%D0%BD%D0%B8%D0%B5)
* [Конфигурация](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_message_handlers.md#%D0%9A%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B0%D1%86%D0%B8%D1%8F)
* [Обработчик команд](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_message_handlers.md#%D0%9E%D0%B1%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D1%87%D0%B8%D0%BA-%D0%BA%D0%BE%D0%BC%D0%B0%D0%BD%D0%B4)
* [Обработчик событий](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_message_handlers.md#%D0%9E%D0%B1%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D1%87%D0%B8%D0%BA-%D1%81%D0%BE%D0%B1%D1%8B%D1%82%D0%B8%D0%B9)
* [Argument Resolver](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_message_handlers.md#argument-resolver)
* [Валидация](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_message_handlers.md#%D0%92%D0%B0%D0%BB%D0%B8%D0%B4%D0%B0%D1%86%D0%B8%D1%8F)
* [Пример кода](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_message_handlers.md#%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80-%D0%BA%D0%BE%D0%B4%D0%B0)

#### Введение
Все обработчики представлены в виде публичных методов класса, у которых есть 2 обязательных аргумента (идущих первыми):
* Объект сообщения (реализующего интерфейс [Command](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/Contract/Messages/Command.php) или [Event](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/Contract/Messages/Event.php)). [Подробнее о сообщениях](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_messages.md)
* Объект [KernelContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php). [Побробнее про контекст](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_context.md)

Методы могут возвращать void, \Generator и [Promise](https://github.com/amphp/amp/blob/master/lib/Promise.php)
Название методов с точки зрения инфраструктуры значения не имеет.
В классе могут содержаться вспомогательные методы, которые не помечены как обработчики

#### Конфигурация
В [примере инициализации](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_initialization.md) указан вариант автоматической регистрации всех обработчиков. Если по каким-либо причинам автоматическая регистрация не подходит, вы можете описать класс в виде сервиса, добавив ему тег ```service_bus.service``` (без значения)

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
