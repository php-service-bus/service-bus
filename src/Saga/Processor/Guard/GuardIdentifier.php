<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Processor\Guard;

use Desperado\Domain\Message\AbstractEvent;
use Desperado\ServiceBus\Saga\Identifier\AbstractSagaIdentifier;
use Desperado\ServiceBus\Saga\Processor\Exceptions\InvalidSagaIdentifierException;

/**
 *
 */
final class GuardIdentifier
{
    /**
     * Check the accessor for the saga ID
     *
     * @param string        $containingIdentifierProperty
     * @param AbstractEvent $event
     *
     * @return void
     *
     * @throws InvalidSagaIdentifierException
     */
    public static function guardIdentifierAccessorExists(string $containingIdentifierProperty, AbstractEvent $event): void
    {
        $identifierAccessorName = \sprintf('get%s', \ucfirst($containingIdentifierProperty));

        if(
            '' === $containingIdentifierProperty ||
            false === \method_exists($event, $identifierAccessorName)
        )
        {
            throw new InvalidSagaIdentifierException(
                \sprintf(
                    'Event "%s" must be contains "%s" accessor that contains the saga ID',
                    $event->getMessageClass(), $identifierAccessorName
                )
            );
        }
    }

    /**
     * Check that the ID value is filled correctly
     *
     * @param string        $identifierValue
     * @param AbstractEvent $event
     *
     * @return void
     *
     * @throws InvalidSagaIdentifierException
     */
    public static function guardIdentifier(string $identifierValue, AbstractEvent $event): void
    {
        if('' === $identifierValue)
        {
            throw new InvalidSagaIdentifierException(
                \sprintf('Identifier value for event "%s" is empty', $event->getMessageClass())
            );
        }
    }

    /**
     * Check that the ID class namespace is filled correctly
     *
     * @param string $identifierNamespace
     *
     * @return void
     *
     * @throws InvalidSagaIdentifierException
     */
    public static function guardIdentifierClassExists(string $identifierNamespace): void
    {
        if(false === \class_exists($identifierNamespace))
        {
            throw new InvalidSagaIdentifierException(
                \sprintf('Identifier class "%s" not exists', $identifierNamespace)
            );
        }

        if(false === \is_a($identifierNamespace, AbstractSagaIdentifier::class, true))
        {
            throw new InvalidSagaIdentifierException(
                \sprintf(
                    'Identifier class "%s" must extends "%s" class',
                    $identifierNamespace, AbstractSagaIdentifier::class

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