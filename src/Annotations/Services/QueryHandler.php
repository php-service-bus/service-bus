<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Annotations\Services;

use Desperado\Domain\Annotations\AbstractAnnotation;

/**
 * Annotation indicating to the query handler
 *
 * @Annotation
 * @Target("METHOD")
 */
class QueryHandler extends AbstractAnnotation implements MessageHandlerAnnotationInterface
{
    /**
     * The namespace of the response to the query.
     * It (event payload) serves as transport for the execution results
     *
     * @var string
     */
    protected $responseEventClass;

    /**
     * Logger channel
     *
     * @var string|null
     */
    protected $loggerChannel;

    /**
     * @inheritdoc
     */
    public function getLoggerChannel(): ?string
    {
        return $this->loggerChannel;
    }

    /**
     * Get response event class
     *
     * @return string
     */
    public function getResponseEventClass(): string
    {
        return $this->responseEventClass;
    }
}
