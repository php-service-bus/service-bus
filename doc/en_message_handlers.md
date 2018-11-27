Table of contents
* [Introduction](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_message_handlers.md#introduction)
* [Configuration](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_message_handlers.md#configuration)
* [Command handler](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_message_handlers.md#command-handler)
* [Event listener](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_message_handlers.md#event-listener)
* [Argument Resolver](https://github.com/mmasiukevich/service-bus/blob/master/doc/message_handlers.md#argument-resolver)
* [Validation](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_message_handlers.md#validation)
* [Code example](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_message_handlers.md#code-example)

#### Introduction
All handlers are represented as public class methods, which have 2 required arguments (going first):
* Message object (implements [Command](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/Contract/Messages/Command.php) or [Event](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/Contract/Messages/Event.php)) interface. [More about the messages](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_messages.md)
* [KernelContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php) object. [More about the context](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_context.md)

Methods can return void, \Generator and [Promise](https://github.com/amphp/amp/blob/master/lib/Promise.php) types.
Naming of the methods doesn't matter. A class may contain supportive methods, which are not designated as handlers.

#### Configuration
In the [initialization example](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_initialization.md) an automatic registration of all handlers option is pointed out. If, by any reasons, an automatic registration isn’t suitable, you can describe a class as a service, by adding ```service_bus.service``` tag (without value)

#### Command handler
Command handler is distinguished by [CommandHandler](https://github.com/mmasiukevich/service-bus/blob/master/src/Services/Annotations/CommandHandler.php) annotation

#### Event listener
Subscriber is distinguished by [EventListener](https://github.com/mmasiukevich/service-bus/blob/master/src/Services/Annotations/EventListener.php) annotation

#### Argument Resolver
Automatic transmission of interactions into a handler method is implemented. The object of message and context, as it has been noted, is always present. Aside from it any of registered services can be designated.

For example:
```php
    public function renameCustomer(
        RenameCustomerCommand $command,
        KernelContext $context,
        SagaProvider $sagaProvider
    ): \Generator {/** ... */}
```
We can apply a arbitrary quantity of dependencies. Only the common sense puts limitations. Possibly, a part of dependencies is better to be put out into the constructor and configure individually. Yet, this question goes beyond the scope of the framework possibilities description.

#### Validation
For command and event handlers a validation can be switched on, if it is necessary. The rules of validation are described by the annotations in the message class. Based on [SymfonyValidation](https://symfony.com/doc/current/validation.html)

#### Code example
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
