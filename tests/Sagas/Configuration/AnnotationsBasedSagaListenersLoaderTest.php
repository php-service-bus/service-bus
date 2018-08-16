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

namespace Desperado\ServiceBus\Tests\Sagas\Configuration;

use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Sagas\Configuration\AnnotationsBasedSagaListenersLoader;
use Desperado\ServiceBus\Tests\Sagas\Configuration\Stubs\SagaWithoutAnnotations;
use Desperado\ServiceBus\Tests\Sagas\Configuration\Stubs\SagaWithoutListeners;
use Desperado\ServiceBus\Tests\Sagas\Configuration\Stubs\SagaWrongIdClassSpecified;
use Desperado\ServiceBus\Tests\Sagas\Configuration\Stubs\TestSagaStoreImplementation;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class AnnotationsBasedSagaListenersLoaderTest extends TestCase
{
    /**
     * @var SagaProvider
     */
    private $sagaProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->sagaProvider = new SagaProvider(new TestSagaStoreImplementation);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->sagaProvider);
    }


    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Configuration\Exceptions\InvalidSagaConfiguration
     * @expectedExceptionMessage Could not find class-level annotation
     *                           "Desperado\ServiceBus\Sagas\Annotations\SagaHeader" in
     *                           "Desperado\ServiceBus\Tests\Sagas\Configuration\Stubs\SagaWithoutAnnotations"
     *
     * @return void
     */
    public function sagaWithoutAnnotations(): void
    {
        (new AnnotationsBasedSagaListenersLoader($this->sagaProvider))->load(SagaWithoutAnnotations::class);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Configuration\Exceptions\InvalidSagaConfiguration
     * @expectedExceptionMessage In the meta data of the saga
     *                           "Desperado\ServiceBus\Tests\Sagas\Configuration\Stubs\SagaWrongIdClassSpecified", an
     *                           incorrect value of the "idClass"
     *
     * @return void
     */
    public function sagaWithIncorrectHeaderAnnotationData(): void
    {
        (new AnnotationsBasedSagaListenersLoader($this->sagaProvider))->load(SagaWrongIdClassSpecified::class);
    }

    /**
     * @test
     *
     * @return void
     */
    public function sagaWithoutListeners(): void
    {
        $result = (new AnnotationsBasedSagaListenersLoader($this->sagaProvider))->load(SagaWithoutListeners::class);

        static::assertEmpty($result);
    }
}
