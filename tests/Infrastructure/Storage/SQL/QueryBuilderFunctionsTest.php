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

namespace Desperado\ServiceBus\Tests\Infrastructure\Storage\SQL;

use function Desperado\ServiceBus\Common\uuid;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\cast;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\insertQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\deleteQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\notEqualsCriteria;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\selectQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\toSnakeCase;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\updateQuery;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class QueryBuilderFunctionsTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function selectQuery(): void
    {
        $query = selectQuery('test', 'id', 'value')
            ->where(equalsCriteria('id', '100500'))
            ->compile();

        static::assertSame(
            'SELECT "id", "value" FROM "test" WHERE "id" = ?', $query->sql()
        );

        static::assertEquals(['100500'], $query->params());
    }

    /**
     * @test
     *
     * @return void
     */
    public function updateQuery(): void
    {
        $query = updateQuery('test', ['name' => 'newName', 'email' => 'newEmail'])
            ->where(equalsCriteria('id', '100500'))
            ->compile();

        static::assertSame(
            'UPDATE "test" SET "name" = ?, "email" = ? WHERE "id" = ?', $query->sql()
        );

        static::assertEquals(['newName', 'newEmail', '100500'], $query->params());
    }

    /**
     * @test
     *
     * @return void
     */
    public function deleteQuery(): void
    {
        $query = deleteQuery('test')->compile();

        static::assertSame('DELETE FROM "test"', $query->sql());
        static::assertEmpty($query->params());
    }

    /**
     * @test
     *
     * @return void
     */
    public function insertQueryFromObject(): void
    {
        $object = new class('qwerty', 'root')
        {
            private $first;
            private $second;

            /**
             * @param $first
             * @param $second
             */
            public function __construct($first, $second)
            {
                /** @noinspection UnusedConstructorDependenciesInspection */
                $this->first = $first;
                /** @noinspection UnusedConstructorDependenciesInspection */
                $this->second = $second;
            }
        };

        $query = insertQuery('test', $object)->compile();

        static::assertSame(
            'INSERT INTO "test" ("first", "second") VALUES (?, ?)',
            $query->sql()
        );

        static::assertEquals(['qwerty', 'root'], $query->params());
    }


    /**
     * @test
     *
     * @return void
     */
    public function insertQueryFromArray(): void
    {
        $query = insertQuery('test', ['first' => 'qwerty', 'second' => 'root'])->compile();

        static::assertSame(
            'INSERT INTO "test" ("first", "second") VALUES (?, ?)',
            $query->sql()
        );

        static::assertEquals(['qwerty', 'root'], $query->params());
    }

    /**
     * @test
     *
     * @return void
     */
    public function toSnakeCase(): void
    {
        static::assertSame(
            'some_snake_case', toSnakeCase('someSnakeCase')
        );
    }


    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage The "key" property must contain a scalar value. "array" given
     *
     * @return void
     */
    public function castNonScalarType(): void
    {
        /** @noinspection PhpParamsInspection */
        cast('key', []);
    }

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage "Closure" must implements "__toString" method
     *
     * @return void
     */
    public function castObjectWithoutToString(): void
    {
        cast(
            'key',
            function()
            {

            }
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function castObjectWithToString(): void
    {
        $object = new class()
        {
            public function __toString()
            {
                return 'qwerty';
            }
        };

        static::assertSame('qwerty', cast('key', $object));
    }

    /**
     * @test
     *
     * @return void
     */
    public function objectNotEqualsCriteria(): void
    {
        $object = new class()
        {
            /** @var string */
            private $id;

            public function __construct()
            {
                $this->id = uuid();
            }

            public function __toString()
            {
                return $this->id;
            }
        };

        $query = selectQuery('test')->where(notEqualsCriteria('id', $object))->compile();

        static::assertEquals('SELECT * FROM "test" WHERE "id" != ?', $query->sql());
        static::assertEquals([(string) $object], $query->params());
    }

    /**
     * @test
     *
     * @return void
     */
    public function scalarNotEqualsCriteria(): void
    {
        $id = uuid();

        $query = selectQuery('test')->where(notEqualsCriteria('id', $id))->compile();

        static::assertEquals('SELECT * FROM "test" WHERE "id" != ?', $query->sql());
        static::assertEquals([$id], $query->params());
    }
}
