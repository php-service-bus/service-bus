<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Serializer;

use Desperado\ServiceBus\AbstractSaga;

/**
 * Saga serializer
 */
class SagaSerializer implements SagaSerializerInterface
{
    private const DEFAULT_GZIP_LEVEL = 7;

    /**
     * Gzip level
     *
     * @var int
     */
    private $gzipLevel;

    /**
     * @param int $gzipLevel
     */
    public function __construct($gzipLevel = self::DEFAULT_GZIP_LEVEL)
    {
        $this->gzipLevel = $gzipLevel;
    }

    /**
     * @inheritdoc
     */
    public function serialize(AbstractSaga $saga): string
    {
        return \base64_encode(
            \gzencode(
                \serialize($saga),
                $this->gzipLevel
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $serializedSaga): AbstractSaga
    {
        return \unserialize(
            \gzdecode(
                \base64_decode($serializedSaga)
            )
        );
    }
}
