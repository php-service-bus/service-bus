#### Message handlers

All handlers are represented as **public** class methods, which have 2 required arguments:
* Message object (Command or Event). The message object **MUST** be the first argument;
* [ServiceBusContext](https://github.com/php-service-bus/common/blob/v5.0/src/Context/ServiceBusContext.php) object.

Methods can return `void`, `\Generator` and `Promise` types. Naming of the methods doesn't matter. A class may contain supportive methods, which are not designated as handlers.

#### Configuration
In the [initialization example](./configuration.md) an automatic registration of all handlers option is pointed out. If, by any reasons, an automatic registration isn’t suitable, you can describe a class as a service, by adding `service_bus.service` tag (without value).

#### Attributes
* Command handler is distinguished by [CommandHandler](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Services/Attributes/CommandHandler.php#L52) attribute;
* * `description`: Handler description. Will be added to the log when the method is called;
  * `validationEnabled`: Enable message validation (via `symfony/validator`);
  * `validationGroups`: Validation groups;
  * `executionTimeout`: Execution timeout (in seconds). By default, 600;
* Subscriber is distinguished by [EventListener](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Services/Attributes/EventListener.php#L43) attribute.
* * `description`: Listener description. Will be added to the log when the method is called;
  * `validationEnabled`: Enable message validation (via `symfony/validator`);
  * `validationGroups`: Validation groups;

#### Argument Resolver

Supports automatic dependency injection as handler arguments. he object of message and context, as it has been noted, is always present. Aside from it any of registered services can be designated.

```php
    public function renameCustomer(
        RenameCustomerCommand $command,
        KernelContext $context,
        SagaProvider $sagaProvider
    ): Promise {/** ... */}
```

You can apply arbitrary quantity of dependencies. Only the common sense puts limitations. Possibly, a part of dependencies is better to be put out into the constructor and configure individually, but this question goes beyond the scope of the framework possibilities description.
