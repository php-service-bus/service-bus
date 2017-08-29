<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\MessageBus;

use Desperado\ConcurrencyFramework\Domain\Context\ContextInterface;
use Desperado\ConcurrencyFramework\Domain\MessageBus\MessageBusInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\CommandInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Domain\Pipeline\PipelineCollection;
use Desperado\ConcurrencyFramework\Domain\Pipeline\PipelineEntry;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Pipeline\Pipeline;

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
        $pipelineName = $message instanceof CommandInterface
            ? 'command'
            : 'event';

        if(false === $this->pipelines->has($pipelineName))
        {
            $this->pipelines->add(new Pipeline($pipelineName));
        }

        $this->pipelines
            ->get($pipelineName)
            ->run()
            ->send(new PipelineEntry($message, $context));

    }
}
