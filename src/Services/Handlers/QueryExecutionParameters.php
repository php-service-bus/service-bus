<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services\Handlers;

/**
 * Query handler options
 */
final class QueryExecutionParameters extends AbstractMessageExecutionParameters
{
    /**
     * The namespace of the response to the query.
     * It (event payload) serves as transport for the execution results
     *
     * @var string
     */
    private $responseEventClass;

    /**
     * @param string $loggerChannel
     * @param string $responseEventClass
     */
    public function __construct(string $loggerChannel, string $responseEventClass)
    {
        parent::__construct($loggerChannel);

        $this->responseEventClass = $responseEventClass;
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
