<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\Exceptions;

/**
 * An error occurred during the serialization of the message
 */
final class EncodeMessageFailed extends \RuntimeException implements SerializationFail
{

}
