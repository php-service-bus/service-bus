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
use Desperado\ServiceBus\Saga\Configuration\SagaConfiguration;
use Desperado\ServiceBus\Saga\Configuration\SagaListenerConfiguration;
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

        /** @var SagaConfiguration $baseConfiguration */
        $baseConfiguration = $extractor->extractSagaConfiguration(Positive\TestConfigurationSaga::class);

        static::assertInstanceOf(SagaConfiguration::class, $baseConfiguration);

        static::assertEquals('+10 days', $baseConfiguration->getExpireDateModifier());
        static::assertEquals(Positive\TestConfigurationSagaIdentity::class, $baseConfiguration->getIdentifierNamespace());
        static::assertEquals('operationId', $baseConfiguration->getContainingIdentifierProperty());
        static::assertEquals(Positive\TestConfigurationSaga::class, $baseConfiguration->getSagaNamespace());

        $eventListeners = $extractor->extractSagaListeners(Positive\TestConfigurationSaga::class);

        static::assertCount(2, $eventListeners);

        foreach($eventListeners as $listenerConfig)
        {
            /** @var SagaListenerConfiguration $listenerConfig */

            static::assertEquals(Positive\TestConfigurationSaga::class, $listenerConfig->getSagaClass());

            if(Positive\TestConfigurationSagaEvent::class === $listenerConfig->getEventClass())
            {
                static::assertFalse($listenerConfig->hasCustomIdentifierProperty());
            }
            else
            {
                static::assertTrue($listenerConfig->hasCustomIdentifierProperty());
                static::assertEquals('customIdentifierField', $listenerConfig->getContainingIdentifierProperty());
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
