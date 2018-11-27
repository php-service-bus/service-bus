Оглавление
* [Что такое Event Sourcing](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%A7%D1%82%D0%BE-%D1%82%D0%B0%D0%BA%D0%BE%D0%B5-event-sourcing)
* [Область применения](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%9E%D0%B1%D0%BB%D0%B0%D1%81%D1%82%D1%8C-%D0%BF%D1%80%D0%B8%D0%BC%D0%B5%D0%BD%D0%B5%D0%BD%D0%B8%D1%8F)
* [Поток событий](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%9F%D0%BE%D1%82%D0%BE%D0%BA-%D1%81%D0%BE%D0%B1%D1%8B%D1%82%D0%B8%D0%B9)
* [Проблемы](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%9F%D1%80%D0%BE%D0%B1%D0%BB%D0%B5%D0%BC%D1%8B)
* [Снимки (Snapshot)](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%A1%D0%BD%D0%B8%D0%BC%D0%BA%D0%B8-snapshot)
* [Представления (Projections)](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%9F%D1%80%D0%B5%D0%B4%D1%81%D1%82%D0%B0%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F-projections)
* [Индексы](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%98%D0%BD%D0%B4%D0%B5%D0%BA%D1%81%D1%8B)
* [Пример агрегата](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80-%D0%B0%D0%B3%D1%80%D0%B5%D0%B3%D0%B0%D1%82%D0%B0)
* [Доступные методы](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%94%D0%BE%D1%81%D1%82%D1%83%D0%BF%D0%BD%D1%8B%D0%B5-%D0%BC%D0%B5%D1%82%D0%BE%D0%B4%D1%8B)
* [Жизненный цикл](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%96%D0%B8%D0%B7%D0%BD%D0%B5%D0%BD%D0%BD%D1%8B%D0%B9-%D1%86%D0%B8%D0%BA%D0%BB)
* [Работа с индексами](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%A0%D0%B0%D0%B1%D0%BE%D1%82%D0%B0-%D1%81-%D0%B8%D0%BD%D0%B4%D0%B5%D0%BA%D1%81%D0%B0%D0%BC%D0%B8)
* [Работа со снимками](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%A0%D0%B0%D0%B1%D0%BE%D1%82%D0%B0-%D1%81%D0%BE-%D1%81%D0%BD%D0%B8%D0%BC%D0%BA%D0%B0%D0%BC%D0%B8)
* [Примеры кода](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B-%D0%BA%D0%BE%D0%B4%D0%B0)

#### Что такое Event Sourcing
Классическая схема работы предполагает то, что в базе у нас хранится именно конечное состояние агрегата.
Например, у нас есть ```User<string:name, string:email, string:password_hash>```. Все данные хранятся в базе данных в виде таблицы users с одноимёнными полями.
Минусы такого подхода в том, что мы не можем увидеть истории изменения данных пользователя: когда, как, кем и при каких условиях они были изменены, нет возможности отменить часть операций

При использовании Event Sourcing у нас хранится не конечное состояние, а последовательность событий, применённых к агрегату.
Например: ```UserRegisteredEvent, UserPasswordChangedEvent, UserEmailChangedEvent, UserPasswordChangedEvent```. Чтобы воссоздать текущее состояние агрегата нам необходимо по очереди применить все эти события в том порядке, в котором они были получены.
Как итог мы получаем полную историю изменений

Более подробное описание: [Event sourcing](https://microservices.io/patterns/data/event-sourcing.html)

#### Область применения
Из-за ряда особенностей данного подхода использовать его повсеместно не получится. Event Sourcing не является серебрянной пулей.
Его использование оправдано там, где есть вероятность множества изменений, которые надо как-то контролировать.

Приведу пример:
у нас есть агрегат payment, который включает в себя информацию о платеже. Платёж может переходить во множество статусов: может быть отменён как полностью, так и частично и т.д. С точки зрения бизнесс логики может быть очень важно видеть историю и как-то на неё влиять. Здесь Event Sourcing подойдёт как нельзя лучше

А бывает другой вариант - список валют. Валюты обычно включает в себя ISO 4217 коды (буквенный и цифровой), которые никогда не меняются. Фактически - это константы. Следить за историей их изменения (которого не произойдёт) явно нет смысла и посему Event Sourcing тут не подходит

#### Поток событий
Поток событий - это упорядоченный список событий, которые были применены в рамках агерата.
Каждое новое событие увеличивает версию потока на 1.

#### Проблемы
Существует несколько проблем данного подхода.
Во-первых, это избыточность данных. Мы храним огромное кол-во ненормализованных данных (событий).
Во-вторых, необходимо затратить ресурсы на конвертацию потока событий в агрегат.
В-третьих, у нас нет возможности искать по каким-либо полям (ведь полей у нас нет, есть лишь сериализованное представление события)

#### Снимки (Snapshot)
Для решения проблемы, связанной с необходимостью накладывать множество событий на агрегат используются снимки.
Снимок - сериализованное представление агрегата какой-либо версии (например, 10)
Когда в следующий раз мы захотим получить текущее состояние агрегата для версии 20, нам не обязательно накладывать все предшествующие 20 событий. Достаточно получить снимок 10-ой версии и применить к нему недостающие события (т.е. ещё 10)

#### Представления (Projections)
Эффективная работа с Event Sourcing предполагает разделение на 2 интерфейса: write model (наш агрегат) и read model (представление).
Представление - это то, с чем будут работать клиенты (например, через API). Оно формируется на основании изменений и в том виде, в котором необходимо. По сути представление - это просто ключ и набор данных, которые были собраны специально под тип запроса.
Данный подход позволяет полностью исключить из работы все запросы с соединениями, группировками и т.д., ибо данные уже сохранены в том виде, в котором необходимы для использования.

#### Индексы
Для решения проблемы, связанной с фильтрацией данных, можно взять любое key\value хранилище для реализации маппинга.
Например, нам необходимо обеспечить уникальность email пользователя.
В классической Event Sourcing имплементации это если и возможно, то весьма затратно. Но можно поступить иначе:
когда мы создаём пользователя, мы записываем его идентификатор и email в специальное хранилище. Когда мы будем создавать другого пользователя, мы можем проверить, используется ли у кого-либо данный email, или нет.
Также, благодаря такому подходу решается проблема поиска ([Пример с использованием индексов](https://github.com/mmasiukevich/service-bus/blob/master/doc/ru_event_sourcing.md#%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B-%D0%BA%D0%BE%D0%B4%D0%B0))

#### Пример агрегата
```php
final class Customer extends Aggregate
{
    private $name;
    private $email;

    public static function register(CustomerId $id,  string $name, string $email): self
    {
        $self = new self();

        $self->raise(new CustomerRegisteredEvent($id, $name, $email));

        return $self;
    }

    public function rename(string $newName): self
    {
        $this->raise(
            new CustomerRenamed(
                $this->name, $newName
            )
        );
    }

    private function onCustomerRegisteredEvent(CustomerRegisteredEvent $event): void
    {
        $this->name  = $event->name;
        $this->email = $event->email;
    }

    private function onCustomerRenamed(CustomerRenamed $event): void
    {
        $this->name = $event->newName;
    }
}
```
#### Доступные методы
В рамках агрегата доступны следуюшие защищённые методы:
* [close()](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/Aggregate.php#L138): Аналог soft delete. Мы не удаляем весь поток событий, лишь помечаем его закрытым. Закрытый поток нельзя получить и, как следствие, изменить
* [raise()](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/Aggregate.php#L108): Применение события к агрегату. Именование обработчиков событий происходит по шаблону ```on{ClassName}```, где *on* - префикс, *{ClassName}* - базовое название класса события

#### Жизненный цикл
При создании нового агрегата в транспорт будет отправлено событие [AggregateCreated](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/Contract/AggregateCreated.php), при закрытии агрегата - [AggregateClosed](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/Contract/AggregateClosed.php)

#### Работа с индексами
Для работы с индексами используется [IndexProvider](https://github.com/mmasiukevich/service-bus/blob/master/src/IndexProvider.php), у которого есть следующие методы (все возвращают объект [Promise](https://github.com/amphp/amp/blob/master/lib/Promise.php)):
* [get()](https://github.com/mmasiukevich/service-bus/blob/master/src/IndexProvider.php#L54): Получить сохранённое значение
* [has()](https://github.com/mmasiukevich/service-bus/blob/master/src/IndexProvider.php#L91): Было ли значение сохранено
* [add()](https://github.com/mmasiukevich/service-bus/blob/master/src/IndexProvider.php#L126): Добавить значение в индекс. По принципу работы схож с методом ```\Memcached:add```: если значения с таким ключём не существовало, вернёт ```true```, в противном случае - ```false```
* [remove()](https://github.com/mmasiukevich/service-bus/blob/master/src/IndexProvider.php#L165): Удаляет сохранённое значение
* [update()](https://github.com/mmasiukevich/service-bus/blob/master/src/IndexProvider.php#L181): Обновляет сохранённое значение

#### Работа со снимками
По умолчанию реализована лишь 1 стратегия генерации снимков - [SnapshotVersionTrigger](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcingSnapshots/Trigger/SnapshotVersionTrigger.php), основанная на версиях (создавать снимок каждые N версий). Снимки создаются автоматически, за исключением выбора стратегии (реализовать свои можно с помощью интерфейса [SnapshotTrigger](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcingSnapshots/Trigger/SnapshotTrigger.php)) никаких настроект не требуется

#### Примеры кода
```php
$context = new KernelContext();
   $registerCustomerCommand = new RegisterCommand(/** payload */);

   $databaseAdapter = StorageAdapterFactory::inMemory();

   $eventSourcingProvider = new EventSourcingProvider(
       new SqlEventStreamStore($databaseAdapter),
       new Snapshotter(
           new SqlSnapshotStore($databaseAdapter),
           new SnapshotVersionTrigger()
       )
   );

   $indexer = new IndexProvider(
       new SqlIndexesStorage($databaseAdapter)
   );

   $customerId = CustomerId::new();

   $indexKey   = IndexKey::create('customer', 'email');
   $indexValue = IndexValue::create($registerCustomerCommand->email);

   if(true === yield $indexer->add($indexKey, $indexValue))
   {
       $customer = Customer::register(
           $customerId,
           $registerCustomerCommand->name,
           $registerCustomerCommand->email
       );

       yield $eventSourcingProvider->save($customer, $context);

       $customer->rename('root');

       yield $eventSourcingProvider->save($customer, $context);
   }
   else
   {
       throw new \RuntimeException('Non unique email');
   }
```
