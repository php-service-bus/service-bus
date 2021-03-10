#### Demo application

There is a [small demo application](https://github.com/php-service-bus/demo) that will allow you to study the main aspects of the framework.
To run the example, you only need Docker and Docker Compose.

#### Project structure
The project structure is not much different from any other application. All code is located inside the `./src` directory.
Inside the `./tools` directory there are prepared sample commands needed to run the examples.

The entry point is the file `./bin/consumer`.
It initializes the application and starts a subscription to the message bus.

[Read more about configuration](./configuration.md)

#### Messages

A message is any structure that is exchanged between different actors. It is generally accepted that messages are semantically divided into 2 types: commands and events.
- Command is an order to do something;
- Event is a consequence of something that happened (for example, due to changes in the data caused by the execution of the command).

[Read more](./messages.md)

#### Context
Each message that is processed by the framework has its own context. It is essentially a bridge between the transport layer and the immediate handler.
The context stores inside itself where the message came from, what metadata was attached to it, etc.

[Read more](./context.md)

#### Example
Run the following commands to launch the application:

```
git clone git@github.com:php-service-bus/demo.git
cd demo
make start consumer-logs
```
Once launched, you will have an application that will implement several handlers that demonstrate the basic functionality:

- Command handlers
- Event listeners
- Sagas
- Event Sourcing

Let's try, for example, to start creating a client profile:

```
make new-customer
```

The mechanics of the example are simple: the `RegisterCustomer` command will be sent to the message bus (the example uses `RabbitMQ`). As soon as the customer aggregate is created, the `CustomerRegistered` event will be sent to the message bus.

#### New application

In order to start implementing your own project, the easiest way is to execute the commands:

```
composer create-project php-service-bus/skeleton my-project
```

After installation, you will have a completely ready-to-use application with test handlers

#### Documentation

Documentation can be found in the [.documentation directory](./)

* [Configuration](./configuration.md)
* [Transport](./transport.md)
* [Storage](./database.md)
* [Sagas](./sagas.md)
* [EventSourcing](./event_sourcing.md)
* [HttpClient](./http_client.md)
* [Scheduler](./scheduler.md)
* [Cache](./cache.md)
* [Mutex](./mutex.md)
