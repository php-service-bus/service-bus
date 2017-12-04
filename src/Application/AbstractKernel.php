<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Application;

use Desperado\Domain\CQRS\ContextInterface;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Framework\MessageProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Application kernel
 */
abstract class AbstractKernel
{
    /**
     * Message execution processor
     *
     * @var MessageProcessor
     */
    private $messageProcessor;

    /**
     * Container instance
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param MessageProcessor   $messageProcessor
     * @param ContainerInterface $container.
     */
    public function __construct(MessageProcessor $messageProcessor, ContainerInterface $container)
    {
        $this->messageProcessor = $messageProcessor;
        $this->container = $container;
    }

    /**
     * Handle message
     *
     * @param AbstractMessage  $message
     * @param ContextInterface $context
     *
     * @return void
     */
    final public function handle(AbstractMessage $message, ContextInterface $context): void
    {
        $context = $this->createApplicationContext($context);

        $this->messageProcessor->execute($message, $context);
    }

    /**
     * Create application-level context
     *
     * @param ContextInterface $parentContext
     *
     * @return ContextInterface
     */
    abstract protected function createApplicationContext(ContextInterface $parentContext): ContextInterface;

    /**
     * Get message execution processor
     *
     * @return MessageProcessor
     */
    final protected function getMessageProcessor(): MessageProcessor
    {
        return $this->messageProcessor;
    }

    /**
     * Get DI container
     *
     * @return ContainerInterface
     */
    final protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
