<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus;

use Desperado\Domain\DomainExceptionInterface;

/**
 * The service bus component exception marker
 */
interface ServiceBusExceptionInterface extends DomainExceptionInterface
{

}
