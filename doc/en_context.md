Context serves as a link between current message processing and a transport layer. By default, [KernelContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php) is available in the application.

Let us review the possibilities, provided by the Context a little closer:

- Sending a message into a transport.
  - There are several scenarios of sending a message (the [Promise](https://github.com/amphp/amp/blob/master/lib/Promise.php)) object is returned in each of them):
    - [delivery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L66): Sends a message(s) into a transport by default. The sending process is happening with default headers (the best choice in most cases)
    - [send()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L87): Sends a command with specified headers into a transport
    - [publish()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L106): Sends an event with specified headers into a transport
- [incomingEnvelope](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L127): Receiving an envelope for current message. Includes various data about a received message
- [logContextMessage()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L135): Logs a message, adding an identity to record (it is generated in the moment the message is received from the queue)
- [logContextThrowable()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L152): Is analogue [logContextMessage()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L135), but serves to log exceptions

You can use your context, implementing a [MessageDeliveryContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/ExecutionContext/MessageDeliveryContext.php) and [LoggingInContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/ExecutionContext/LoggingInContext.php) (optionally)
