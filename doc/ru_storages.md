Оглавление
* [Пулы соединений](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_storages.md#%D0%9F%D1%83%D0%BB%D1%8B-%D1%81%D0%BE%D0%B5%D0%B4%D0%B8%D0%BD%D0%B5%D0%BD%D0%B8%D0%B9)
* [Интерфейс StorageAdapter](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_storages.md#storageadapter)
* [Адаптер для работы с PostgreSQL](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_storages.md#%D0%90%D0%B4%D0%B0%D0%BF%D1%82%D0%B5%D1%80-%D0%B4%D0%BB%D1%8F-%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D1%8B-%D1%81-postgresql)
* [Адаптер для работы с DoctrineDBAL (используется только для тестирования)](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_storages.md#%D0%90%D0%B4%D0%B0%D0%BF%D1%82%D0%B5%D1%80-%D0%B4%D0%BB%D1%8F-%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D1%8B-%D1%81-doctrinedbal)
* [Транзакции](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_storages.md#%D0%A2%D1%80%D0%B0%D0%BD%D0%B7%D0%B0%D0%BA%D1%86%D0%B8%D0%B8)
* [Обработка результатов запросов](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_storages.md#resultset)
* [QueryBuilder](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_storages.md#querybuilder)

#### Пулы соединений

Пул соединений с базой данных это набор заранее открытых соединений с базой данных используемый для предоставления соединения в тот момент, когда оно требуется. 
Используются для повышения производительности при работе с базами данных. Помимо ускорения работы с базой данных, решается проблема с вложенными транзакциями.
Из 2х представленных адаптеров пул соединений поддерживает только [AmpPostgreSQLAdapter](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/SQL/AmpPostgreSQL/AmpPostgreSQLAdapter.php). 
Если вкратце, то работает это так:

Есть коллекция соединений. При необходимости выполнить запрос, соединение извлекается (другие его уже получить не смогут), выполняется операция и затем оно возвращается в коллекцию, доступным для последующей работы.

#### StorageAdapter
Приложение построено на базе фреймворка [AMP](https://amphp.org/), что позволяет обеспечить конкурентность выполняемых операций. К сожалению, на текущий момент инфраструктура не позволяет использовать абсолютное большинство драйверов в неблокирующем режиме.
Цель данного интерфейса - уницифировать различные решения (как блокирующие, так и неблокирующие).

#### Адаптер для работы с PostgreSQL
[Неблокирующий адаптер](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/SQL/AmpPostgreSQL/AmpPostgreSQLAdapter.php), который поддерживает пул соединений. Реализовано на базе [Async Postgres client ](https://github.com/amphp/postgres)
По умолчанию кол-во соединений равно 100, время жизни неиспользуемого соденинения равно 60 секундам (по истечении которых соединение будет закрыто и удалено из пула). Если при получении соединения в пуле не оказалось доступных, то будет создано новое. По завершению выполнения добавится в коллекцию на общих условиях.

#### Адаптер для работы с DoctrineDBAL
Данные адаптер должен использоваться **исключительно для тестов**. Он представляет из себя абстракцию над [DoctrineDBAL](https://github.com/doctrine/dbal)
Не поддерживает пул соединений

#### Транзакции
Транзакции представлены в виде интерфейса [TransactionAdapter](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/TransactionAdapter.php)
Для адаптера [AmpPostgreSQLAdapter](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/SQL/AmpPostgreSQL/AmpPostgreSQLAdapter.php) используется уровень изоляции [Read Committed](https://postgrespro.com/docs/postgrespro/9.5/transaction-iso#xact-read-committed). Каждая транзакция выполняется в рамках своего подключения к базе данных (получается из пула соединений). 
В [DoctrineDBALAdapter](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/SQL/DoctrineDBAL/DoctrineDBALAdapter.php) реализованы только в целях совместимости и, опять-таки, для тестирования. Уровень изоляции зависит от значения по умолчанию у выбранного драйвера

#### ResultSet

Для работы с результатом выполнения [execute()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/StorageAdapter.php#L35) используется [ResultSet](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/ResultSet.php)
По сути представляет собой итератор с несколькими дополнительными методами:

* [advance()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/ResultSet.php#L31): возвращает true, если в итераторе есть значение, которое можно получить в методе [getCurrent()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/ResultSet.php#L40)
* [getCurrent()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/ResultSet.php#L40): возвращает текущий элемент
* [lastInsertId()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/ResultSet.php#L51): Возвращает идентификатор последней добавленной записи
  * Для PostgreSQL необходимо воспользоваться конструкцией [RETURNING](https://www.postgresql.org/docs/9.1/static/sql-insert.html), вернув поле ```id``` (именование важно)
* [affectedRows](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/ResultSet.php#L60): Возвращает кол-во строк, которое было затронуто операциями INSERT/UPDATE/DELETE

Для упрощения работы с результатом есть вспомогательные функции, которые позволяют получить результат в виде массива:  [fetchOne()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/functions.php#L65), и [fetchAll()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/functions.php#L33)

#### QueryBuilder
Для упрощения работы с SQL используется библиотека [shadowhand/latitude](https://github.com/shadowhand/latitude). 
В рамках приложения поверх неё реализовано несколько функций-помошников:

* [queryBuilder()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/SQL/queryBuilderFunctions.php#L32): Создаёт объект билдера запроса для указанного адаптера (по умолчанию PostgreSQL)
* [equalsCriteria()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/SQL/queryBuilderFunctions.php#L45): Условие равенства
* [notEqualsCriteria()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/SQL/queryBuilderFunctions.php#L63): Условия неравенства
* [selectQuery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/SQL/queryBuilderFunctions.php#L83): Создаёт объект билдера SELECT запроса для PostgreSQL
* [updateQuery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/SQL/queryBuilderFunctions.php#L96): Создаёт объект билдера UPDATE запроса для PostgreSQL
* [deleteQuery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/SQL/queryBuilderFunctions.php#L108): Создаёт объект билдера DELETE запроса для PostgreSQL
* [insertQuery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Infrastructure/Storage/SQL/queryBuilderFunctions.php#L121): Создаёт объект билдера INSERT запроса для PostgreSQL
