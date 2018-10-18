Контекст служит связующим звеном между текущей обработкой сообщения и транспортным слоём.
По умолчанию, в приложении доступен [KernelContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php)

Разберём чуть подробнее возможности, которые он предоставляет:

- [delivery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L128): Отправка сообщения
- [logContextMessage()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L140): Логирует сообщение, добавляя ему идентификатор сообщения (генерируется в момент получения сообщения из очереди)
- [logContextThrowable()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L150): аналогичен [logContextMessage()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L140)), но для логирования исключений
- [isValid()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L103): Если включена валидация, содержит флаг корректности входящего сообщения
- [violations](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L118): Если включена валидация и есть ошибки, вернёт их коллекцию

С точки зрения фреймворка контекст можно написать свой, реализовав интерфейс [MessageDeliveryContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/ExecutionContext/MessageDeliveryContext.php) и опционально [LoggingInContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/ExecutionContext/LoggingInContext.php)