<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Infrastructure\Storage\SQL\ActiveRecord;

use Amp\Promise;
use function Amp\Promise\wait;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Infrastructure\Cache\InMemoryStorage;
use Desperado\ServiceBus\Infrastructure\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapterFactory;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class ActiveRecordTest extends TestCase
{
    /**
     * @var AmpPostgreSQLAdapter
     */
    private $adapter;

    /**
     * @inheritdoc
     *
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        InMemoryStorage::instance()->reset();

        $this->adapter = StorageAdapterFactory::create(
            AmpPostgreSQLAdapter::class,
            (string) \getenv('TEST_POSTGRES_DSN')
        );

        $promise = $this->adapter->execute(<<<EOT
CREATE TABLE IF NOT EXISTS test_table 
(
    id uuid PRIMARY KEY,
    first_value varchar NOT NULL,
    second_value varchar NOT NULL
)
EOT
        );

        wait($promise);

        $promise = $this->adapter->execute(<<<EOT
        CREATE TABLE IF NOT EXISTS second_test_table
(
	pk serial constraint second_test_table_pk PRIMARY KEY,
	title bytea NOT NULL
);
EOT
        );

        wait($promise);
    }

    /**
     * @inheritdoc
     *
     * @throws \Throwable
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        wait($this->adapter->execute('DROP TABLE test_table'));
        wait($this->adapter->execute('DROP TABLE second_test_table'));
        unset($this->adapter);

        InMemoryStorage::instance()->reset();
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function findNonExistent(): void
    {
        $testTable = wait(TestTable::find($this->adapter, uuid()));

        static::assertNull($testTable);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function storeNew(): void
    {
        $expectedId = uuid();

        /** @var TestTable $testTable */
        $testTable = wait(
            TestTable::new(
                $this->adapter,
                ['id' => $expectedId, 'first_value' => 'first', 'second_value' => 'second']
            )
        );

        $id = $testTable->lastInsertId();

        static::assertEquals($expectedId, $id);

        /** @var TestTable $testTable */
        $testTable = wait(TestTable::find($this->adapter, $id));

        static::assertNotNull($testTable);
        static::assertEquals('first', $testTable->first_value);
        static::assertEquals('second', $testTable->second_value);
    }


    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function updateStored(): void
    {
        $id = uuid();

        /** @var TestTable $testTable */
        $testTable = wait(TestTable::new($this->adapter, ['id' => $id, 'first_value' => 'first', 'second_value' => 'second']));

        wait($testTable->save());

        $testTable->first_value = 'qwerty';

        wait($testTable->save());

        unset($testTable);

        $testTable = wait(TestTable::find($this->adapter, $id));

        static::assertEquals('qwerty', $testTable->first_value);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function deleteUnStored(): void
    {
        /** @var TestTable $testTable */
        $testTable = wait(TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => 'first', 'second_value' => 'second']));

        wait($testTable->remove());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function updateWithNoChanges(): void
    {
        $id = uuid();
        /** @var TestTable $testTable */
        $testTable = wait(TestTable::new($this->adapter, ['id' => $id, 'first_value' => 'first', 'second_value' => 'second']));

        wait($testTable->save());

        static::assertEquals($id, $testTable->lastInsertId());
        static::assertEquals(0, wait($testTable->save()));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function findCollection(): void
    {
        $collection = [
            TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => '1', 'second_value' => '7']),
            TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => '2', 'second_value' => '6']),
            TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => '3', 'second_value' => '5']),
            TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => '4', 'second_value' => '4']),
            TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => '5', 'second_value' => '3']),
            TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => '6', 'second_value' => '2']),
            TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => '7', 'second_value' => '1'])
        ];

        /** @var Promise $promise */
        foreach($collection as $promise)
        {
            /** @var TestTable $entity */
            $entity = wait($promise);

            wait($entity->save());
        }

        /** @var TestTable[] $result */
        $result = wait(TestTable::findBy($this->adapter, [], 3));

        static::assertCount(3, $result);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function successRemove(): void
    {
        $testTable = wait(TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => 'first', 'second_value' => 'second']));

        wait($testTable->save());
        wait($testTable->remove());

        /** @var TestTable[] $result */
        $result = wait(TestTable::findBy($this->adapter, []));

        static::assertCount(0, $result);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function saveWithNoPrimaryKey(): void
    {
        /** @var TestTable $testTable */
        $testTable = wait(TestTable::new($this->adapter, ['first_value' => 'first', 'second_value' => 'second']));
        wait($testTable->save());
    }

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage Column "qqqq" does not exist in table "test_table"
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function unExistsProperty(): void
    {
      wait(TestTable::new($this->adapter, ['qqqq' =>'111']));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function saveWithSerialPrimaryKey(): void
    {
        /** @var SecondTestTable $table */
        $table = wait(SecondTestTable::new($this->adapter, ['title' => 'root']));

        unset($table);

        /** @var SecondTestTable[] $tables */
        $tables = wait(SecondTestTable::findBy($this->adapter));

        static::assertCount(1, $tables);

        /** @var SecondTestTable $table */
        $table = \reset($tables);

        static::assertEquals('root', $table->title);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function refresh(): void
    {
        /** @var SecondTestTable $table */
        $table = wait(SecondTestTable::new($this->adapter, ['title' => 'root']));

        static::assertTrue(isset($table->pk));

        $table->title = 'qwerty';

        wait($table->refresh());

        static::assertEquals('root', $table->title);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function selectWithOrder(): void
    {
        wait(TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => '1', 'second_value' => '3']));
        wait(TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => '2', 'second_value' => '2']));
        wait(TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => '3', 'second_value' => '1']));

        /** @var TestTable[] $collection */
        $collection = wait(TestTable::findBy($this->adapter, [], 50, ['first_value' => 'desc']));

        static::assertCount(3, $collection);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failed to update entity: data has been deleted
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function refreshWithDeletedEntry(): void
    {
        /** @var TestTable $table */
        $table = wait(TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => '1', 'second_value' => '3']));

        wait($table->remove());
        wait($table->refresh());
    }

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage In the parameters of the entity must be specified element with the index "id" (primary key)
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function updateWithoutPrimaryKey(): void
    {
        /** @var TestTable $table */
        $table = wait(TestTable::new($this->adapter, ['id' => uuid(), 'first_value' => '1', 'second_value' => '3']));

        $table->id = null;

        wait($table->save());
    }
}
