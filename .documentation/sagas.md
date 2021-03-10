#### What is Saga?
Saga may be interpreted as any documented business process which consists of steps. Speaking technically, Saga is an Event Listener which listens to some event and performs an action based on that event.  
A good example is a flowchart with a decision symbol.

There are synchronous, asynchronous and mixed sagas (where some steps may be performed synchronously and some asynchronously). From personal experience only asynchronous sagas are worth implementing.

A little bit more on [Saga](https://microservices.io/patterns/data/saga.html).

#### Field of use
* Description of complicated business processes. A good example is electronic document management. Transfer of a document may take some time - up to weeks depending on many factors. The process itself consists of a dozen of steps including electronic signatures of 3 parties (3 party is the operator)
* Distributed transactions (emulated Atomicity): either each step would be finished successfully or a step-specific compensating action will be performed.

#### Implementation features
All the sagas in the framework are asynchronous and have their own state which is serialized and stored in database. Any variables except closures may be used as saga state.
Any non-closed saga may be triggered starting from any of saga's steps if a corresponding event is received.

#### Caveats
The more sagas (and steps within them) you have the more documentation you need for them. Single saga may trigger other sagas heavily increasing complexity of business processes.
[Message based architecture](https://www.enterpriseintegrationpatterns.com/patterns/messaging/Messaging.html) is very difficult to understand at the beginning (especially after "common" PHP programming) and requires a lot of responsibility.

#### Installation
```bash
composer req php-service-bus/sagas
```

Enable module:
```php
$bootstrap->applyModules(
    SagaModule::withSqlStorage(DatabaseAdapter::class)->enableAutoImportSagas([__DIR__ . '/src'])
);
```

#### Saga configuration

Sagas are configures through [SagaHeader](https://github.com/php-service-bus/sagas/blob/v5.0/src/Configuration/Attributes/SagaHeader.php) and [SagaEventListener](https://github.com/php-service-bus/sagas/blob/v5.0/src/Configuration/Attributes/SagaEventListener.php) attributes.

[SagaHeader](https://github.com/php-service-bus/sagas/blob/v5.0/src/Configuration/Attributes/SagaHeader.php) is used to configure saga meta information
* `idClass`: Saga identifier class namespace;
* `containingIdSource`: The `event` property (or `headers key`) that contains the saga ID;
* `containingIdProperty`: Field (in the `event` or `key in headers`) where saga identifier should be found. Each event should be bound to specific saga instance (since one message may be received by different sagas) - that's why there should be an identifier in each of saga events;
* `expireDateModifier`: Saga expiry interval.

[SagaEventListener](https://github.com/php-service-bus/sagas/blob/v5.0/src/Configuration/Attributes/SagaEventListener.php) is used to configure event listener details
* `containingIdSource`: Allows you to override the same value set in the meta information. If specified, then these parameters will be used for that particular listener;
* `containingIdProperty`: Allows you to override the same value set in the meta information. If specified, then these parameters will be used for that particular listener;
* `description`: Listener description. Will be added to the log when the method is called.

Each event listener should be named like `on{EventClassName}`, where `on` is a generic prefix and `{EventClassName}` is short class name.
For example:

```php
   public function onSomethingChanges(SomethingChanges $event): void {}
```

#### Lifecycle
Saga execution starts on call of method [start()](https://github.com/php-service-bus/sagas/blob/v5.0/src/SagasProvider.php#L82). There are following methods available inside Saga instance:
* [id()](https://github.com/php-service-bus/sagas/blob/v5.0/src/Saga.php#L126): Receive saga id;
* [createdAt()](https://github.com/php-service-bus/sagas/blob/v5.0/src/Saga.php#L134): Receive saga creation date;
* [expireDate()](https://github.com/php-service-bus/sagas/blob/v5.0/src/Saga.php#L142): Receive saga expiration date;
* [closedAt()](https://github.com/php-service-bus/sagas/blob/v5.0/src/Saga.php#L150): Receive the closing date of the saga;
* [stateHash()](https://github.com/php-service-bus/sagas/blob/v5.0/src/Saga.php#L158): Infrastructure method, used to get a hash of the current state. It is compared with the previous one and based on the changes it is decided whether the saga needs to be updated in the database.

also some protected methods:

* [raise()](https://github.com/php-service-bus/sagas/blob/v5.0/src/Saga.php#L168): Dispatches an event;
* [fire()](https://github.com/php-service-bus/sagas/blob/v5.0/src/Saga.php#L181): Dispatches a command;
* [makeCompleted()](https://github.com/php-service-bus/sagas/blob/v5.0/src/Saga.php#L195): Closes saga marking it as successfully finished.
* [makeFailed()](https://github.com/php-service-bus/sagas/blob/v5.0/src/Saga.php#L210): Closes saga marking it as failed.

On saga status change next events will be raised:
* [SagaCreated](https://github.com/php-service-bus/sagas/blob/v5.0/src/Contract/SagaCreated.php)
* [SagaStatusChanged](https://github.com/php-service-bus/sagas/blob/v5.0/src/Contract/SagaStatusChanged.php)
* [SagaClosed](https://github.com/php-service-bus/sagas/blob/v5.0/src/Contract/SagaClosed.php)
* [SagaReopened](https://github.com/php-service-bus/sagas/blob/v5.0/src/Contract/SagaReopened.php)

#### Creation
There is a specific provider created for more convenient operations with sagas - [SagaProvider](https://github.com/php-service-bus/sagas/blob/v5.0/src/SagasProvider.php). Each of its methods returns `Promise` object.
* [start()](https://github.com/php-service-bus/sagas/blob/v5.0/src/SagasProvider.php#L82): Creates and starts new saga firing a command;
* [obtain()](https://github.com/php-service-bus/sagas/blob/v5.0/src/SagasProvider.php#L125): Retrieves a saga instance from database;
* [save()](https://github.com/php-service-bus/sagas/blob/v5.0/src/SagasProvider.php#L178): Saves all changes in saga state and sends all saga events to transport;
* [reopen()](https://github.com/php-service-bus/sagas/blob/v5.0/src/SagasProvider.php#L223): Restart a previously closed saga.

#### Example
```php
final class ExampleSagaId extends SagaId
{

}

#[SagaHeader(
    idClass: ExampleSagaId::class,
    containingIdProperty: 'operationId',
    expireDateModifier: '+1 year'
)]
final class ExampleSaga extends Saga
{
    public function start(Command $command): void
    {
        $this->fire(
            new SomeCommand(/** params */)
        );
    }

    #[SagaEventListener]
    private function onSomeEvent(SomeEvent $event): void
    {
        $this->fire(
            new NextCommand(/**  */)
        );
    }
}
```

```php
$saga = yield $sagaProvider->start(
    ExampleSagaId::new(ExampleSaga::class),
    new SomeStartCommand(),
    new KernelContext()
);
```

On saga creation each saga will be started and saved. It is not necessary to save a saga if there were no changes to its state after start. Saga should be saved if it was obtained from database.

Saga uniqueness is ensured by a composite key which consists of an UUID and identifier class namespace. Identifier class namespace should be used here to allow launching different sagas with the same identifier (usually all of these are parts of a single business process).