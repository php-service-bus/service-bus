<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Common;

/**
 * Create datetime object from valid string
 *
 * @noinspection PhpDocMissingThrowsInspection
 *
 * @param null|string $datetimeString
 *
 * @return \DateTimeImmutable|null
 */
function datetimeInstantiator(?string $datetimeString): ?\DateTimeImmutable
{
    if(null !== $datetimeString && '' !== $datetimeString)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new \DateTimeImmutable($datetimeString);
    }

    return null;
}

/**
 * Receive datetime as string representation (or null if not specified)
 *
 * @param \DateTimeInterface|null $dateTime
 * @param string                  $format
 *
 * @return string|null
 */
function datetimeToString(?\DateTimeInterface $dateTime, string $format = 'Y-m-d H:i:s'): ?string
{
    if(null !== $dateTime)
    {
        return $dateTime->format($format);
    }

    return null;
}
