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
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use function Desperado\ServiceBus\Common\readReflectionPropertyValue;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Sagas\Configuration\Exceptions\IdentifierClassNotFound;
use Desperado\ServiceBus\Sagas\Configuration\Exceptions\IncorrectIdentifierFieldSpecified;
use Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier;
use Desperado\ServiceBus\Sagas\SagaId;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SagaListenerOptions  $sagaListenerOptions
     * @param SagaProvider         $sagaProvider
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        SagaListenerOptions $sagaListenerOptions,
        SagaProvider $sagaProvider,
        LoggerInterface $logger = null
    )
    {
        $this->sagaListenerOptions = $sagaListenerOptions;
        $this->sagaProvider        = $sagaProvider;
        $this->logger              = $logger ?? new NullLogger();
    }

    /**
     * Apply received event
     *
     * @param Event                  $event
     * @param MessageDeliveryContext $context
     *
     * @psalm-suppress MixedTypeCoercion
     *
     * @return Promise<bool>
     */
    public function execute(Event $event, MessageDeliveryContext $context): Promise
    {
        $sagaListenerOptions = $this->sagaListenerOptions;
        $sagaProvider        = $this->sagaProvider;
        $logger              = $this->logger;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(Event $event, MessageDeliveryContext $context) use (
                $sagaListenerOptions, $sagaProvider, $logger
            ): \Generator
            {
                $id = self::searchSagaIdentifier($event, $sagaListenerOptions);

                /** @var \Desperado\ServiceBus\Sagas\Saga|null $saga */
                $saga = yield $sagaProvider->obtain($id);

                if(null !== $saga)
                {
                    invokeReflectionMethod($saga, 'applyEvent', $event);

                    yield $sagaProvider->save($saga, $context);

                    return true;
                }

                $logger->info('Saga with identifier "{sagaId}:{sagaClass}" not found', [
                        'sagaId'    => (string) $id,
                        'sagaClass' => \get_class($id)
                    ]
                );

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
                $propertyValue = (string) readReflectionPropertyValue($event, $propertyName);
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
