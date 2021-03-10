#### Messages
A message is any structure that is exchanged between different actors. It is generally accepted that messages are semantically divided into 2 types: commands and events.

In message-oriented applications, it is extremely important to separate message types using naming conventions. It should all reflect business operations.

#### Command
A command is an order to do something.
There are many opinions as to whether the team can return any value or not. In the current implementation, due to the nuances of the asynchronous interaction scheme, the message handlers (command\event) can't return the result.
Note: For a specific command, there can be **only 1 handler**.
#### Event
Event - consequence of something that happened (For example, due to changes in the data caused by the execution of the command). There can be an unlimited number of subscribers for an event.