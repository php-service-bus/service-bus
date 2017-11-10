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
use Desperado\Domain\EntryPoint\EntryPointInterface;
use Desperado\Domain\Message\MessageInterface;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use React\Promise\PromiseInterface;

/**
 * Application entry point
 */
final class EntryPoint implements EntryPointInterface
{
    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPointName;

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * Application kernel
     *
     * @var AbstractKernel
     */
    private $kernel;

    /**
     * @param string                     $entryPointName
     * @param MessageSerializerInterface $messageSerializer
     * @param AbstractKernel             $kernel
     */
    public function __construct(
        string $entryPointName,
        MessageSerializerInterface $messageSerializer,
        AbstractKernel $kernel
    )
    {
        $this->entryPointName = $entryPointName;
        $this->messageSerializer = $messageSerializer;
        $this->kernel = $kernel;
    }

    /**
     * @inheritdoc
     */
    public function getEntryPointName(): string
    {
        return $this->entryPointName;
    }

    /**
     * @inheritdoc
     */
    public function getMessageSerializer(): MessageSerializerInterface
    {
        return $this->messageSerializer;
    }

    /**
     * @inheritdoc
     *
     * @return PromiseInterface
     */
    public function handleMessage(MessageInterface $message, ContextInterface $context): PromiseInterface
    {
        return $this->kernel->handle($message, $context);
    }
}
