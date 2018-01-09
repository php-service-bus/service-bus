<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services\Handlers\Exceptions;

/**
 * Collection of exception handlers
 */
class ExceptionHandlersCollection implements \IteratorAggregate
{
    /**
     * Collection of exception handlers
     *
     * @var ExceptionHandlerData[]
     */
    private $collection;

    /**
     * Create collection
     *
     * @param ExceptionHandlerData[] $handlers
     *
     * @return ExceptionHandlersCollection
     */
    public static function create(array $handlers = []): self
    {
        $self = new self();

        foreach($handlers as $handler)
        {
            $self->add($handler);
        }

        return $self;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): \Generator
    {
        return yield from $this->collection;
    }

    /**
     * Add to collection
     *
     * @param ExceptionHandlerData $exceptionHandlerData
     *
     * @return void
     */
    public function add(ExceptionHandlerData $exceptionHandlerData): void
    {
        $this->collection[] = $exceptionHandlerData;
    }

    /**
     * Search handler for specified message/exception
     *
     * @param string $messageNamespace
     * @param string $exceptionNamespace
     *
     * @return \Closure|null
     */
    public function searchHandler(string $messageNamespace, string $exceptionNamespace): ?\Closure
    {
        $messageNamespace = \ltrim($messageNamespace, '\\');
        $exceptionNamespace = \ltrim($exceptionNamespace, '\\');

        foreach($this as $item)
        {
            /** @var ExceptionHandlerData $item */

            if(
                $messageNamespace === $item->getMessageClassNamespace() &&
                $exceptionNamespace === $item->getExceptionClassNamespace()
            )
            {
                return $item->getExceptionHandler();
            }
        }

        return null;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {
        $this->collection = [];
    }
}
