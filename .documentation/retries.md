#### Introduction

In the process of work, problems often arise with the processing of messages, which must be somehow solved. But this simple task also has its pitfalls.
Let's look at a simple example:

We have an event `OrderCreated` and 10 listeners are registered for it in the application.
In the framework, all handlers are executed independently of each other. Thus, 4/10 handlers can be executed for us, 5 and 6 will complete with an error, the rest will also be successful.

In this case, we cannot simply send the message back to the queue, because some of our handlers have already performed some actions and their repetition can lead the system to an inconsistent state.

The problem has several solutions. The first is to multiplex the messages you send. For example, [NServiceBus](https://particular.net/nservicebus) does this: when sending, it creates a copy of the message for each of the recipients.
And the receivers (in our case, event listeners) handle everything independently.
This is a completely reliable approach, but it has obvious drawbacks:
* "Extra" messages appear in the queue, which were just multiplexing;
* The sender knows about all the recipients, which slightly violates the concept and leads to additional complications in the microservice architecture, where each of the services can be written in different languages.

The second solution is a little more complicated and it is this one that is implemented in the framework:
Each message handler has its own identifier. If some of the handlers for the community end with an error, the framework will send a copy of the message to the queue, indicating which handlers were not executed correctly.
And the next time when a message is received, not 10 handlers will be called, but only 2: those in which there was an error. This allows you to handle non-standard situations quite flexibly, and also not to face the problems that will arise in the first case.
If the number of attempts to complete the message is exceeded, the message will be saved in the database.
> You need to create a table in the database: [failed_messages.sql](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Retry/schema/failed_messages.sql)
#### Implementation

* [SimpleRetryStrategy](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Retry/SimpleRetryStrategy.php)
* [NullRetryStrategy](https://github.com/php-service-bus/service-bus/blob/v5.0/src/Retry/NullRetryStrategy.php)
