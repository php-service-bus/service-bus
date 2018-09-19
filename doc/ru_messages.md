Оглавление
* [Команды (Command)](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_messages.md#%D0%9A%D0%BE%D0%BC%D0%B0%D0%BD%D0%B4%D1%8B-command)
* [События (Event)](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_messages.md#%D0%A1%D0%BE%D0%B1%D1%8B%D1%82%D0%B8%D1%8F-event)
* [Запросы (Query)](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_messages.md#%D0%97%D0%B0%D0%BF%D1%80%D0%BE%D1%81%D1%8B-query)

#### Команды (Command)
Команда - указание на то, что необходимо совершить какое-либо действие. Если провести аналогию с http, то это метод POST.
Существует множество мнений относительно того, может ли команда возвращать какое-либо значение, но в рамках фреймворка команда значений не возвращает. Это нюансы асинхронной работы (да, можно сделать всё так, что бы команда возвращала результат, если вызов был из обработчика и в ряде случаев это могло бы быть удобно, но от этой идеи отказался).
Для команды может быть зарегистрирован только 1 обработчик.

Все команды должны реализовывать интерфейс [Command](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/Contract/Messages/Command.php)

#### События (Event)
Событие - отражение чего-то, что произошло. Выступают как следствие выполнение команды.
Для события может быть неограниченное кол-во слушателей.

Все события должны реализовывать интерфейс [Event](https://github.com/mmasiukevich/service-bus/blob/master/src/Common/Contract/Messages/Event.php)

#### Запросы (Query)
Запросы - это сообщения для получение информации. Если провести аналогию с http, то это метод GET. Они никогда не приводят к изменению данных.
Данный тип сообщений не реализован на уровне MessageBus, и приложения в целом.

