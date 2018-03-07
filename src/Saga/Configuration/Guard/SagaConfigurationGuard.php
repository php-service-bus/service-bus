<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Configuration\Guard;

use Desperado\Domain\DateTime;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\ServiceBus\Saga\Configuration\Exceptions as ConfigurationExceptions;

/**
 * Saga configuration guard helpers
 */
final class SagaConfigurationGuard
{
    /**
     * Verifying the validity of the expiration date modifier
     *
     * @param string $datetimeModifier
     *
     * @return void
     *
     * @throws ConfigurationExceptions\EmptyExpirationDateModifierException
     * @throws ConfigurationExceptions\IncorrectExpirationDateModifierException
     */
    public static function assertExpireDateIsValid(string $datetimeModifier): void
    {
        if('' === $datetimeModifier)
        {
            throw new ConfigurationExceptions\EmptyExpirationDateModifierException;
        }

        $currentDateTimestamp = DateTime::now()->toTimestamp();
        $expireDateTimestamp = DateTime::fromString($datetimeModifier)->toTimestamp();

        if(null === $expireDateTimestamp || $currentDateTimestamp >= $expireDateTimestamp)
        {
            throw new ConfigurationExceptions\IncorrectExpirationDateModifierException();
        }
    }

    /**
     * Validate identity class namespace
     *
     * @param string $sagaIdentityClassNamespace
     *
     * @return void
     *
     * @throws ConfigurationExceptions\EmptyIdentifierNamespaceException
     * @throws ConfigurationExceptions\IdentifierClassNotFoundException
     */
    public static function assertIdentifierClassNamespaceIsValid(string $sagaIdentityClassNamespace): void
    {
        if('' === $sagaIdentityClassNamespace)
        {
            throw new ConfigurationExceptions\EmptyIdentifierNamespaceException();
        }

        if(false === \class_exists($sagaIdentityClassNamespace))
        {
            throw new ConfigurationExceptions\IdentifierClassNotFoundException($sagaIdentityClassNamespace);
        }
    }

    /**
     * Assert field that contains the saga identifier specified
     *
     * @param string $containingIdentifierProperty
     *
     * @return void
     *
     * @throws ConfigurationExceptions\EmptyIdentifierFieldValueException
     */
    public static function assertContainingIdentifierPropertySpecified(string $containingIdentifierProperty): void
    {
        if('' === $containingIdentifierProperty)
        {
            throw new ConfigurationExceptions\EmptyIdentifierFieldValueException();
        }
    }

    /**
     * Assert correct handler arguments
     *
     * @param string                 $sagaNamespace
     * @param \ReflectionParameter[] $arguments
     *
     * @return void
     *
     * @throws ConfigurationExceptions\InvalidEventSubscriberArgumentException
     */
    public static function guardFirstEventListenerArgumentIsEvent(string $sagaNamespace, array $arguments): void
    {
        if(
            false === isset($arguments[0]) ||
            null === $arguments[0]->getClass() ||
            false === $arguments[0]->getClass()->isSubclassOf(AbstractEvent::class)
        )
        {
            throw new ConfigurationExceptions\InvalidEventSubscriberArgumentException(
                \sprintf(
                    'The event handler for the saga "%s" should take the first argument to the object '
                    . 'that extends the "%s"',
                    $sagaNamespace, AbstractEvent::class
                )
            );
        }
    }

    /**
     * Close constructor
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {

    }
}
