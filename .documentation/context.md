#### Context
Each message that is processed by the framework has its own context. It is essentially a bridge between the transport layer and the immediate handler.

Each context implements the [ServiceBusContext](https://github.com/php-service-bus/common/blob/v5.0/src/Context/ServiceBusContext.php) interface. The default implementation is [KernelContext](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Context/KernelContext.php).
Let us review the possibilities, provided by the Context a little closer:
* [violations()](https://github.com/php-service-bus/common/blob/v5.0/src/Context/ServiceBusContext.php#L26): If the validation of the received message was enabled and there are errors, then this method will return a collection of violations;
* [delivery()](https://github.com/php-service-bus/common/blob/v5.0/src/Context/ServiceBusContext.php#L35): Enqueue message
* [deliveryBulk()](https://github.com/php-service-bus/common/blob/v5.0/src/Context/ServiceBusContext.php#L52): Enqueue message in transaction mode (If the transport implementation does not allow transactional dispatch of messages, concurrent dispatch will be performed.);
* [logger()](https://github.com/php-service-bus/common/blob/v5.0/src/Context/ServiceBusContext.php#L61): Obtaining a PSR-3 compatible logger instance, which will add meta information about the received message to each record. For example, received packet ID, trace message ID, etc;
* [headers()](https://github.com/php-service-bus/common/blob/v5.0/src/Context/ServiceBusContext.php#L68): Getting an array of headers of a received message;
* [metadata()](https://github.com/php-service-bus/common/blob/v5.0/src/Context/ServiceBusContext.php#L73): Retrieving the metadata of a received message.

#### Metadata
Metadata should be understood as meaning such as:
* [traceId](https://github.com/php-service-bus/common/blob/v5.0/src/Context/IncomingMessageMetadata.php#L28): Message flow identifier. In fact, it allows you to track a chain of messages by analogy with OpenTracing;
* [messageId](https://github.com/php-service-bus/common/blob/v5.0/src/Context/IncomingMessageMetadata.php#L23): UUID of received message (generated automatically at the time of receipt);
* [variables()](https://github.com/php-service-bus/common/blob/v5.0/src/Context/IncomingMessageMetadata.php#L35): Any user data.

#### Implementing your own context
Для того, чтобы создать ваш собственный контекст необходимо выполнить 3 действия:
* Implement [ServiceBusContext](https://github.com/php-service-bus/common/blob/v5.0/src/Context/ServiceBusContext.php) interface;
* Implement [ContextFactory](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Context/ContextFactory.php) interface;
* Register your implementation of the [ContextFactory](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Context/ContextFactory.php) interface with the `ServiceBus\Context\ContextFactory` key in the DI container.
