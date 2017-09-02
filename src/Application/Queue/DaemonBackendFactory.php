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

namespace Desperado\Framework\Application\Queue;

use Desperado\Framework\Application\Queue\RabbitMQ\RabbitMqDaemon;
use Desperado\Framework\Domain\Application\DaemonInterface;
use Desperado\Framework\Domain\Serializer\MessageSerializerInterface;
use Desperado\Framework\Infrastructure\Bridge\Logger\LoggerRegistry;
use Psr\Log\LoggerInterface;

/**
 * Daemon backend creation
 */
class DaemonBackendFactory
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
    private $serializer;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string                     $entryPointName
     * @param MessageSerializerInterface $serializer
     */
    public function __construct(
        string $entryPointName,
        MessageSerializerInterface $serializer
    )
    {
        $this->entryPointName = $entryPointName;
        $this->serializer = $serializer;
        $this->logger = LoggerRegistry::getLogger($entryPointName);
    }

    /**
     * Create backend instance
     *
     * @param string $dsn
     *
     * @return DaemonInterface
     */
    public function create(string $dsn): DaemonInterface
    {
        $dsnParts = \parse_url($dsn);

        if(true === isset($dsnParts['scheme']))
        {
            switch($dsnParts['scheme'])
            {
                case 'amqp':

                    return new RabbitMqDaemon(
                        $dsn,
                        $this->entryPointName,
                        $this->logger,
                        $this->serializer
                    );

                default:
                    throw new \InvalidArgumentException('Unsupported daemon specified');
            }
        }

        throw new \InvalidArgumentException('Invalid daemon connection DSN');
    }
}