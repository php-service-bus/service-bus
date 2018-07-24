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

namespace Desperado\ServiceBus\MessageBus\Processor;

use function Amp\call;
use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Sagas\Exceptions\InvalidIdentifier;
use Desperado\ServiceBus\Sagas\Exceptions\Processor\IdentifierClassNotFound;
use Desperado\ServiceBus\Sagas\Exceptions\Processor\ReceiveIdMethodNotFound;
use Desperado\ServiceBus\Sagas\SagaId;
use Desperado\ServiceBus\Sagas\SagaMetadata;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Sagas listeners processor
 */
final class SagaProcessor implements Processor
{
    /**
     * Basic information about saga
     *
     * @var SagaMetadata
     */
    private $sagaMetadata;

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
     * @param SagaMetadata         $sagaMetadata
     * @param SagaProvider         $sagaProvider
     * @param LoggerInterface|null $logger
     */
    public function __construct(SagaMetadata $sagaMetadata, SagaProvider $sagaProvider, LoggerInterface $logger = null)
    {
        $this->sagaMetadata = $sagaMetadata;
        $this->sagaProvider = $sagaProvider;
        $this->logger       = $logger ?? new NullLogger();
    }

    /**
     * @inheritdoc
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\Processor\ReceiveIdMethodNotFound
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\Processor\IdentifierClassNotFound
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidIdentifier
     */
    public function __invoke(Message $message, KernelContext $context): Promise
    {
        $sagaMetadata = $this->sagaMetadata;
        $sagaProvider = $this->sagaProvider;
        $logger       = $this->logger;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(Message $message, KernelContext $context) use ($sagaMetadata, $sagaProvider, $logger): \Generator
            {
                /** @var Event $message $id */

                $id = self::searchSagaIdentifier($message, $sagaMetadata);

                /** @var \Desperado\ServiceBus\Sagas\Saga|null $saga */
                $saga = yield $sagaProvider->obtain($id, $context);

                if(null !== $saga)
                {
                    invokeReflectionMethod($saga, 'applyEvent', $message);

                    yield $sagaProvider->save($saga);

                    return yield new Success();
                }

                $logger->info('Saga with identifier "{sagaId}:{sagaClass}" not found', [
                        'sagaId'    => (string) $id,
                        'sagaClass' => \get_class($id)
                    ]
                );

                return yield new Success();
            },
            $message,
            $context
        );
    }

    /**
     * Search saga identifier object
     *
     * @param Event        $event
     * @param SagaMetadata $sagaMetadata
     *
     * @return SagaId
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\Processor\ReceiveIdMethodNotFound
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\Processor\IdentifierClassNotFound
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidIdentifier
     */
    private static function searchSagaIdentifier(Event $event, SagaMetadata $sagaMetadata): SagaId
    {
        $identifierClass = $sagaMetadata->identifierClass();

        if(true === \class_exists($identifierClass))
        {
            $propertyName = \lcfirst($sagaMetadata->containingIdentifierProperty());

            $methodNames = [
                $propertyName,
                \sprintf('get%s', \ucfirst($propertyName))
            ];

            foreach($methodNames as $methodName)
            {
                if(true === \method_exists($event, $methodName))
                {
                    return self::identifierInstantiator(
                        $identifierClass,
                        $event->{$methodName}(),
                        $sagaMetadata->sagaClass()
                    );
                }
            }

            throw new ReceiveIdMethodNotFound($event, $methodNames);
        }

        throw new IdentifierClassNotFound($identifierClass, $sagaMetadata->sagaClass());
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
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidIdentifier
     */
    private static function identifierInstantiator(string $idClass, string $idValue, string $sagaClass): SagaId
    {
        $identifier = new $idClass($idValue, $sagaClass);

        if($identifier instanceof SagaId)
        {
            return $identifier;
        }

        throw new InvalidIdentifier(
            \sprintf(
                'Saga identifier mus be type of "%s". "%s" type specified',
                SagaId::class, \get_class($identifier)
            )
        );
    }
}
