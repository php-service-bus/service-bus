<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Configuration;

use Desperado\ServiceBus\Saga\Configuration\AnnotationsSagaConfigurationExtractor;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class AnnotationsSagaConfigurationExtractorTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function loadPositiveSagaConfiguration(): void
    {
        $extractor = new AnnotationsSagaConfigurationExtractor();

        $baseConfiguration = $extractor->extractSagaConfiguration(Positive\TestConfigurationSaga::class);

        static::assertArrayHasKey(0, $baseConfiguration);
        static::assertArrayHasKey(1, $baseConfiguration);
        static::assertArrayHasKey(2, $baseConfiguration);

        static::assertEquals('+10 days', $baseConfiguration[0]);
        static::assertEquals(Positive\TestConfigurationSagaIdentity::class, $baseConfiguration[1]);
        static::assertEquals('operationId', $baseConfiguration[2]);

        $eventListeners = $extractor->extractSagaListeners(Positive\TestConfigurationSaga::class);

        static::assertCount(2, $eventListeners);

        foreach($eventListeners as $listener)
        {
            static::assertArrayHasKey(0, $listener);
            static::assertArrayHasKey(1, $listener);

            if(Positive\TestConfigurationSagaEvent::class === $listener[0])
            {
                static::assertNull($listener[1]);
            }
            else
            {
                static::assertNotNull($listener[1]);
                static::assertEquals('customIdentifierField', $listener[1]);
            }
        }
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Configuration\Exceptions\SagaAnnotationNotAppliedException
     * @expectedExceptionMessage Cant find "Desperado\ServiceBus\Annotations\Sagas\Saga" annotation for saga
     *                           "Desperado\ServiceBus\Tests\Saga\Configuration\Negative\SagaWithWrongHeaderAnnotationType"
     *
     * @return void
     */
    public function extractWrongHeaderAnnotation(): void
    {
        $extractor = new AnnotationsSagaConfigurationExtractor();
        $extractor->extractSagaConfiguration(Negative\SagaWithWrongHeaderAnnotationType::class);
    }
}
