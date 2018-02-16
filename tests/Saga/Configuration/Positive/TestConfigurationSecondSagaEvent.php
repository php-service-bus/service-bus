<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Configuration\Positive;

use Desperado\Domain\Message\AbstractEvent;

/**
 *
 */
class TestConfigurationSecondSagaEvent extends AbstractEvent
{
    /**
     *
     *
     * @var string
     */
    protected $customIdentifierField;

    /**
     *
     *
     * @return string
     */
    public function getCustomIdentifierField(): string
    {
        return $this->customIdentifierField;
    }
}
