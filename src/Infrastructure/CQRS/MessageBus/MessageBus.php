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

namespace Desperado\Framework\Infrastructure\CQRS\MessageBus;

use Desperado\Framework\Domain\Context\ContextInterface;
use Desperado\Framework\Domain\MessageBus\MessageBusInterface;
use Desperado\Framework\Domain\Messages\MessageInterface;
use Desperado\Framework\Domain\Pipeline\PipelineCollection;
use Desperado\Framework\Domain\Pipeline\PipelineEntry;
use Desperado\Framework\Infrastructure\CQRS\Pipeline\Pipeline;

/**
 * Message bus
 */
class MessageBus implements MessageBusInterface
{
    /**
     * Pipelines collection
     *
     * @var PipelineCollection
     */
    private $pipelines;

    /**
     * @param PipelineCollection $pipelines
     */
    public function __construct(PipelineCollection $pipelines)
    {
        $this->pipelines = $pipelines;
    }

    /**
     * @inheritdoc
     */
    public function handle(MessageInterface $message, ContextInterface $context): void
    {
        $messageNamespace = \get_class($message);

        if(false === $this->pipelines->has($messageNamespace))
        {
            $this->pipelines->add(new Pipeline($messageNamespace));
        }

        $this->pipelines
            ->get($messageNamespace)
            ->run()
            ->send(new PipelineEntry($message, $context));

    }
}
