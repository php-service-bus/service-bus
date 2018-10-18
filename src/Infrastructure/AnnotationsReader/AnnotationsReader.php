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

namespace Desperado\ServiceBus\Infrastructure\AnnotationsReader;

/**
 * Annotation extractor
 */
interface AnnotationsReader
{
    /**
     * Extract class/method level annotations
     *
     * @param string $class
     *
     * @return AnnotationCollection
     *
     * @throws \Desperado\ServiceBus\Infrastructure\AnnotationsReader\ReadAnnotationFailed
     */
    public function extract(string $class): AnnotationCollection;
}
