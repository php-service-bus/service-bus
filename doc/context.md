Контекст служит связующим звеном между текущей обработкой сообщения и транспортным слоём.
По умолчанию, в приложении доступен [KernelContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php)

Разберём чуть подробнее возможности, которые он предоставляет:

- Отправка сообщения в транспорт
  - Существует несколько вариантов отправки сообщения (все варианты возвращают объект [Promise](https://github.com/amphp/amp/blob/master/lib/Promise.php)):
    - [delivery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L66): Отправляет сообщение (сообщения) в транспорт по умолчанию. Отправка происходит с заголовками по умолчанию (в большинстве случаев самый лучший выбор)
    - [send()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L87): Отправляет в транспорт команду с указанными заголовками
    - [publish()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L106): Отправляет в транспорт событие с указанными заголовками
- [incomingEnvelope](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L127): Получение пакета для текущего сообщения. Содержит различную информации о принятом сообщении.
- [logContextMessage()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L135): Логирует сообщение, добавляя ему идентификатор сообщения (генерируется в момент получения сообщения из очереди)
- [logContextThrowable()](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L152): аналогичен [logContextMessage()])(https://github.com/mmasiukevich/service-bus/blob/master/src/Application/KernelContext.php#L135), но для логирования исключений

С точки зрения фреймворка контекст можно написать свой, реализовав интерфейс [MessageDeliveryContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/ExecutionContext/MessageDeliveryContext.php) и опционально [LoggingInContext](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/ExecutionContext/LoggingInContext.php)