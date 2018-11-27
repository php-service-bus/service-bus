Контекст служит связующим звеном между текущей обработкой сообщения и транспортным слоём.
По умолчанию, в приложении доступен [KernelContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php)

Разберём чуть подробнее возможности, которые он предоставляет:

- [delivery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L117): Отправка сообщения
- [logContextMessage()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L158): Логирует сообщение, добавляя ему идентификатор сообщения (генерируется в момент получения сообщения из очереди)
- [logContextThrowable()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L168): аналогичен [logContextMessage()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L158)), но для логирования исключений
- [isValid()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L92): Если включена валидация, содержит флаг корректности входящего сообщения
- [violations](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L109): Если включена валидация и есть ошибки, вернёт их коллекцию

С точки зрения фреймворка контекст можно написать свой, реализовав интерфейс [MessageDeliveryContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/ExecutionContext/MessageDeliveryContext.php) и опционально [LoggingInContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/ExecutionContext/LoggingInContext.php)
