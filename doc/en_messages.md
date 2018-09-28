Table of contents
* [Command](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_messages.md#command)
* [Event](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_messages.md#event)
* [Query](https://github.com/mmasiukevich/service-bus/blob/master/doc/en_messages.md#query)

#### Command
A command is an order to do something.

There are many opinions as to whether the team can return any value or not. In the current implementation, due to the nuances of the asynchronous interaction scheme, the message handlers (command\event) can\'t return the result. 
All commands must implement the [Command](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/Contract/Messages/Command.php) interface. 

*Note*: For a specific command, there can be only 1 handler

#### Event
Event - consequence of something that happened (For example, due to changes in the data caused by the execution of the command). There can be an unlimited number of subscribers for an event.
All events must implement the [Event](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/Contract/Messages/Event.php) interface. 

#### Query
Query - these are messages for getting information (like GET method in the HTTP protocol). They never change the data.
This type of message is not implemented (In my opinion, this is not a bus task)
