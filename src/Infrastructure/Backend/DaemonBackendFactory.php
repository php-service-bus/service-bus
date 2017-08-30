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

use Desperado\ConcurrencyFramework\Common\Logger\LoggerRegistry;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Backend\RabbitMQ\RabbitMqBackend;
use Desperado\ConcurrencyFramework\Infrastructure\Backend\RedisQueue\RedisQueueBackend;
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
                case 'redis':

                    if(false === isset($dsnParts['path']))
                    {
                        throw new \InvalidArgumentException('Invalid redis queue connection DSN');
                    }

                    return new RedisQueueBackend(
                        $dsnParts['path'],
                        $this->entryPointName,
                        $this->serializer,
                        $this->logger
                    );

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
