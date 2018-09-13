Оглавление
* [Пулы соединений]()
* [Интерфейс StorageAdapter]()
* [Адаптер для работы с PostgreSQL]()
* [Адаптер для работы с DoctrineDBAL (используется только для тестирования)]()
* [Транзакции]()
* [Обработка результатов запросов]()
* [QueryBuilder]()

# Пулы соединений

Пул соединений с базой данных это набор заранее открытых соединений с базой данных используемый для предоставления соединения в тот момент, когда оно требуется. Пулы соединений используются для повышения производительности при работе с базами данных.
Помимо ускорения работы с базой данных, решается проблема с вложенными транзакциями
Из 2х представленных адаптеров пул соединений поддерживает только [AmpPostgreSQLAdapter]()

Если вкратце, то работает это так:

Есть коллекция соединений. При необходимости выполнить запрос, соединение извлекается из коллекции (другие его уже получить не смогут), выполняется операция и соединение возвращается в коллекцию, доступным для последующей работы.

#### StorageAdapter
Приложение построено на базе фреймворка [AMP](https://amphp.org/), что позволяет обеспечить конкурентность выполняемых операций. К сожалению, на текущий момент инфраструктура не позволяет использовать абсолютное большинство драйверов в неблокирующем режиме.
Цель данного интерфейса - уницифировать различные решения (как блокирующие, так и неблокирующие).


#### Адаптер для работы с PostgreSQL
[Неблокирующий адаптер](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/SQL/AmpPostgreSQL/AmpPostgreSQLAdapter.php), который поддерживает пул соединений. Реализовано на базе [Async Postgres client ](https://github.com/amphp/postgres)
По умолчанию кол-во соединений равно 100, время жизни неиспользуемого соденинения равно 60 секундам (по истечении которых соединение будет закрыто и удалено из пула).

#### Адаптер для работы с DoctrineDBAL
Данные адаптер должен использоваться **исключительно для тестов**. Он представляет из себя абстракцию над [DoctrineDBAL](https://github.com/doctrine/dbal)
Не поддерживает пул соединений

#### Транзакции
Транзакции представлены в виде интерфейса [TransactionAdapter](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/TransactionAdapter.php)
Для адаптера [AmpPostgreSQLAdapter](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/SQL/AmpPostgreSQL/AmpPostgreSQLAdapter.php) используется уровень сериализации [Read Committed](https://postgrespro.com/docs/postgrespro/9.5/transaction-iso#xact-read-committed). Каждая транзакция выполняется в рамках своего подключения к базе данных (получается из пула соединений)
В [DoctrineDBALAdapter](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/SQL/DoctrineDBAL/DoctrineDBALAdapter.php) транзакции реализованы только в целях совместимости и, опять-таки, для тестирования. Уровень изоляции транзакций зависит от значения по умолчанию у выбранного драйвера

#### ResultSet

Для работы с результатом выполнения [execute()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/StorageAdapter.php#L35) используется [ResultSet](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/ResultSet.php)
По сути представляет собой итератор с несколькими дополнительными методами:

* [advance](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/ResultSet.php#L37): возвращает true, если в итераторе есть значение, которое можно получить в методе [getCurrent()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/ResultSet.php#L46)
* [getCurrent()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/ResultSet.php#L46): возвращает текущий элемент
* [lastInsertId()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/ResultSet.php#L57): Возвращает идентификатор последней добавленной записи
  * Для PostgreSQL необходимо воспользоваться конструкцией [RETURNING](https://www.postgresql.org/docs/9.1/static/sql-insert.html), вернув поле ```id``` (именования важно)
* [rowsCount](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/ResultSet.php#L66): Возвращает кол-во строк, которое было затронуто операциями INSERT/UPDATE/DELETE

Для упрощения работы с результатами есть вспомогательные функции, которые позволяют получить результат в виде массива:  [fetchOne()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/functions.php#L59), и [fetchAll()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/functions.php#L30)

#### QueryBuilder
Для упрощения работы с SQL используется библиотека [shadowhand/latitude](https://github.com/shadowhand/latitude)
В рамках приложения поверх неё реализовано несколько функций-помошников:

* [queryBuilder()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/SQL/queryBuilderFunctions.php#L29): Создаёт объект билдера запроса для указанного адаптера (по умолчанию PostgreSQL)
* [equalsCriteria()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/SQL/queryBuilderFunctions.php#L42): Условие равенства
* [notEqualsCriteria()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/SQL/queryBuilderFunctions.php#L60): Условия неравенства
* [selectQuery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/SQL/queryBuilderFunctions.php#L78): Создаёт объект билдера SELECT запроса для PostgreSQL
* [updateQuery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/SQL/queryBuilderFunctions.php#L91): Создаёт объект билдера UPDATE запроса для PostgreSQL
* [deleteQuery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/SQL/queryBuilderFunctions.php#L103): Создаёт объект билдера DELETE запроса для PostgreSQL
* [insertQuery()](https://github.com/mmasiukevich/service-bus/blob/master/src/Storage/SQL/queryBuilderFunctions.php#L116): Создаёт объект билдера INSERT запроса для PostgreSQL