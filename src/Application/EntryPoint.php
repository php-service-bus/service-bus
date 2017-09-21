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

use Desperado\Domain\ContextInterface;
use Desperado\Domain\EntryPointInterface;
use Desperado\Domain\Messages\MessageInterface;
use Desperado\Domain\Serializer\MessageSerializerInterface;

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
     */
    public function handleMessage(MessageInterface $message, ContextInterface $context): void
    {
        $this->kernel->handle($message, $context);
    }
}
