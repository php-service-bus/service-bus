<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Tests\Domain;

use Desperado\Framework\Domain\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ParameterBagTest extends TestCase
{
    /**
     * Parameters
     *
     * @var ParameterBag
     */
    private $container;

    /**
     * Container parameters
     *
     * @var array
     */
    private $arrayData;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->arrayData = [
            'string'   => 'someString',
            'numeric'  => '100500',
            'arrayKey' => [
                'someKey' => 'someValue'
            ]
        ];

        $this->container = new ParameterBag($this->arrayData);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->container, $this->arrayData);
    }

    /**
     * @test
     *
     * @return void
     */
    public function base(): void
    {
        static::assertCount(3, $this->container);
        static::assertTrue(\is_iterable($this->container));
        static::assertInstanceOf(\Iterator::class, $this->container->getIterator());
        static::assertTrue($this->container->has('string'));
        static::assertFalse($this->container->has('nonExistsKey'));
        static::assertEquals($this->arrayData, $this->container->all());

        $this->container->remove('string');
        static::assertCount(2, $this->container);
        static::assertFalse($this->container->has('string'));

        $this->container->set('newKey', 'newKeyValue');
        static::assertCount(3, $this->container);
        static::assertTrue($this->container->has('newKey'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function getAsString(): void
    {
        static::assertEquals('someString', $this->container->getAsString('string'));
        static::assertEquals('100500', $this->container->getAsString('numeric'));
        static::assertEquals('default', $this->container->getAsString('nonExistsKey', 'default'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function getAsInt(): void
    {
        static::assertEquals(100500, $this->container->getAsInt('numeric'));
        static::assertEquals(0, $this->container->getAsInt('string'));
        static::assertEquals(-100500, $this->container->getAsInt('nonExistsKey', -100500));
    }

    /**
     * @test
     *
     * @return void
     */
    public function simpleGet(): void
    {
        static::assertEquals($this->arrayData['arrayKey'], $this->container->get('arrayKey'));
        static::assertEquals('someString', $this->container->get('string'));
        static::assertEquals('default', $this->container->get('nonExistsKey', 'default'));
    }
}
