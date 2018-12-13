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

namespace Desperado\ServiceBus\Sagas\Configuration;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\Common\ExecutionContext\LoggingInContext;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use function Desperado\ServiceBus\Common\readReflectionPropertyValue;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Sagas\Configuration\Exceptions\IdentifierClassNotFound;
use Desperado\ServiceBus\Sagas\Configuration\Exceptions\IncorrectIdentifierFieldSpecified;
use Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier;
use Desperado\ServiceBus\Sagas\SagaId;

/**
 * Virtual service (is created for each message) for consistent work with message handlers
 */
final class SagaEventListenerProcessor
{
    /**
     * Listener options
     *
     * @var SagaListenerOptions
     */
    private $sagaListenerOptions;

    /**
     * Saga provider
     *
     * @var SagaProvider
     */
    private $sagaProvider;

    /**
     * @param SagaListenerOptions $sagaListenerOptions
     * @param SagaProvider        $sagaProvider
     */
    public function __construct(SagaListenerOptions $sagaListenerOptions, SagaProvider $sagaProvider)
    {
        $this->sagaListenerOptions = $sagaListenerOptions;
        $this->sagaProvider        = $sagaProvider;
    }

    /**
     * Apply received event
     *
     * @psalm-suppress MixedTypeCoercion Incorrect resolving the value of the promise
     *
     * @param Event                  $event
     * @param MessageDeliveryContext $context
     *
     * @return Promise<bool>
     */
    public function execute(Event $event, MessageDeliveryContext $context): Promise
    {
        $sagaListenerOptions = $this->sagaListenerOptions;
        $sagaProvider        = $this->sagaProvider;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(Event $event, MessageDeliveryContext $context) use (
                $sagaListenerOptions, $sagaProvider
            ): \Generator
            {
                $id = self::searchSagaIdentifier($event, $sagaListenerOptions);

                /** @var \Desperado\ServiceBus\Sagas\Saga|null $saga */
                $saga = yield $sagaProvider->obtain($id, $context);

                if(null !== $saga)
                {
                    invokeReflectionMethod($saga, 'applyEvent', $event);

                    yield $sagaProvider->save($saga, $context);

                    return true;
                }

                if($context instanceof LoggingInContext)
                {
                    $context->logContextMessage('Saga with identifier "{sagaId}:{sagaClass}" not found', [
                            'sagaId'    => (string) $id,
                            'sagaClass' => \get_class($id),
                        ]
                    );
                }

                return true;
            },
            $event,
            $context
        );
    }

    /**
     * Search saga identifier object
     *
     * @param Event               $event
     * @param SagaListenerOptions $sagaListenerOptions
     *
     * @return SagaId
     *
     * @throws \Desperado\ServiceBus\Sagas\Configuration\Exceptions\IncorrectIdentifierFieldSpecified
     * @throws \Desperado\ServiceBus\Sagas\Configuration\Exceptions\IdentifierClassNotFound
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier
     */
    private static function searchSagaIdentifier(Event $event, SagaListenerOptions $sagaListenerOptions): SagaId
    {
        $identifierClass = $sagaListenerOptions->identifierClass();

        if(true === \class_exists($identifierClass))
        {
            $propertyName = \lcfirst($sagaListenerOptions->containingIdentifierProperty());

            try
            {
                $propertyValue = self::readEventProperty($event, $propertyName);
            }
            catch(\Throwable $throwable)
            {
                throw IncorrectIdentifierFieldSpecified::notFound($event, $propertyName);
            }

            if('' !== $propertyValue)
            {
                /** @var SagaId $id */
                $id = self::identifierInstantiator(
                    $identifierClass,
                    $propertyValue,
                    $sagaListenerOptions->sagaClass()
                );

                return $id;
            }

            throw IncorrectIdentifierFieldSpecified::empty($event, $propertyName);
        }

        throw new IdentifierClassNotFound($identifierClass, $sagaListenerOptions->sagaClass());
    }

    /**
     * Read event property value
     *
     * @param Event  $event
     * @param string $propertyName
     *
     * @return string
     *
     * @throws \Throwable Reflection property not found
     */
    private static function readEventProperty(Event $event, string $propertyName): string
    {
        if(true === isset($event->{$propertyName}))
        {
            return (string) $event->{$propertyName};
        }

        return (string) readReflectionPropertyValue($event, $propertyName);
    }

    /**
     * Create identifier instance
     *
     * @template        SagaId
     * @template-typeof SagaId $idClass
     *
     * @param string $idClass
     * @param string $idValue
     * @param string $sagaClass
     *
     * @return SagaId
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier
     */
    private static function identifierInstantiator(string $idClass, string $idValue, string $sagaClass): SagaId
    {
        $identifier = new $idClass($idValue, $sagaClass);

        if($identifier instanceof SagaId)
        {
            return $identifier;
        }

        throw new InvalidSagaIdentifier(
            \sprintf(
                'Saga identifier mus be type of "%s". "%s" type specified',
                SagaId::class, \get_class($identifier)
            )
        );
    }
}
