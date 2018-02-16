<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Serializer;

use Desperado\Domain\Uuid;
use Desperado\ServiceBus\Saga\Metadata\SagaMetadata;
use Desperado\ServiceBus\Saga\Serializer\SagaSerializer;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SagaSerializerTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function serializeFlow(): void
    {
        $serializer = new SagaSerializer();
        $sagaMetadata = SagaMetadata::create(
            SerializerTestSaga::class,
            '+1 day',
            SerializerTestIdentifier::class,
            'requestId'
        );

        $identifier = new SerializerTestIdentifier(Uuid::v4(), SerializerTestSaga::class);
        $command = SerializerTestCommand::create(['testProperty' => '1111']);

        $saga = new SerializerTestSaga($identifier, $sagaMetadata);
        $saga->start($command);

        $restoredSaga = $serializer->unserialize(
            $serializer->serialize($saga)
        );

        static::assertAttributeEquals($identifier, 'id', $restoredSaga);
        static::assertAttributeEquals($command->getTestProperty(), 'testProperty', $restoredSaga);
    }
}
