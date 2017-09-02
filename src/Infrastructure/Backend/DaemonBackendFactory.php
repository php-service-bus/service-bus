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

namespace Desperado\ConcurrencyFramework\Infrastructure\Backend;

use Desperado\ConcurrencyFramework\Domain\Application\BackendInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Logger\LoggerRegistry;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Backend\RabbitMQ\RabbitMqBackend;
use Psr\Log\LoggerInterface;

/**
 * Daemon backend factory
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
     * @return BackendInterface
     */
    public function create(string $dsn): BackendInterface
    {
        $dsnParts = \parse_url($dsn);

        if(true === isset($dsnParts['scheme']))
        {
            switch($dsnParts['scheme'])
            {
                case 'amqp':

                    return new RabbitMqBackend(
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
