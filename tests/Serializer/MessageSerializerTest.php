<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Serializer;

use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\ServiceBus\Serializer\Bridge\SymfonySerializerBridge;
use Desperado\ServiceBus\Serializer\MessageSerializer;

/**
 *
 */
class MessageSerializerTest extends AbstractSerializerTestCase
{
    /**
     * Message serializer
     *
     * @var MessageSerializer
     */
    private $messageSerializer;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->messageSerializer = new MessageSerializer(
            new SymfonySerializerBridge()
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        unset($this->messageSerializer);
    }

    /**
     * @inheritdoc
     */
    protected function getMessageSerializer(): MessageSerializerInterface
    {
        return $this->messageSerializer;
    }
}
