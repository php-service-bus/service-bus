Context serves as a link between current message processing and a transport layer. By default, [KernelContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php) is available in the application.

Let us review the possibilities, provided by the Context a little closer:

- [delivery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L117): Sends a message to recipient (by default, application)
- [logContextMessage()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L158): Logs a message, adding an identity to record (it is generated in the moment the message is received from the queue)
- [logContextThrowable()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L168): Is analogue [logContextMessage()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L158), but serves to log exceptions
- [isValid()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L92): If validation is enabled, the flag contains the correctness of the incoming message
- [violations](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L109): If validation is enabled and there are violations, it will return them

You can use your context, implementing a [MessageDeliveryContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/ExecutionContext/MessageDeliveryContext.php) and [LoggingInContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/ExecutionContext/LoggingInContext.php) (optionally)
