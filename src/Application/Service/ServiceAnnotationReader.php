<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Application\Service;

use Desperado\ConcurrencyFramework\Application\Context\KernelContext;
use Desperado\ConcurrencyFramework\Common\Formatter\ThrowableFormatter;
use Desperado\ConcurrencyFramework\Domain\Annotation\AbstractAnnotation;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Domain\Service\ServiceInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Annotation\AnnotationReader;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Annotation\CommandHandler;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Annotation\ErrorHandler;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Annotation\EventHandler;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\CommandOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\EventOptions;
use Psr\Log\LoggerInterface;

/**
 * Service annotations reader
 */
class ServiceAnnotationReader
{
    /**
     * Annotation reader
     *
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param AnnotationReader $annotationReader
     * @param LoggerInterface  $logger
     */
    public function __construct(AnnotationReader $annotationReader, LoggerInterface $logger)
    {
        $this->annotationReader = $annotationReader;
        $this->logger = $logger;
    }

    /**
     * Extract supported header (class level) annotations
     *
     * @param ServiceInterface $service
     * @param array            $supportedList
     *
     * @return AbstractAnnotation[]
     */
    public function extractHeaderAnnotations(ServiceInterface $service, array $supportedList): array
    {
        return \array_filter(
            \array_map(
                function(AbstractAnnotation $annotation) use ($supportedList)
                {
                    return true === \in_array(\get_class($annotation), $supportedList, true)
                        ? $annotation
                        : null;
                },
                $this->annotationReader->loadClassAnnotations($service)
            )
        );
    }

    /**
     * Extract handlers
     *
     * [
     *     'messages' => [
     *         'SomeMessageNamespace' => [
     *              0 => [
     *                  'forMessage' => 'SomeMessageNamespace',
     *                  'handler'    => \Closure::class,
     *                  'options'    => AbstractOptions::class
     *              ]
     *          ]
     *     ],
     *     'errors' => [
     *         0 => [
     *             'exceptionClass' => \Exception::class,
     *             'forMessage' => 'SomeMessageNamespace',
     *             'handler'    => \Closure::class,
     *         ]
     *     ]
     * ]
     *
     * @param ServiceInterface $service
     * @param string|null      $defaultServiceLoggerChannel
     *
     * @return array
     */
    public function extractHandlers(ServiceInterface $service, string $defaultServiceLoggerChannel = null)
    {
        $list = [];
        $messageHandlers = $this->annotationReader->loadClassMethodsAnnotation($service);

        foreach($messageHandlers as $annotationData)
        {
            try
            {
                /** @var AbstractAnnotation $annotation */
                $annotation = $annotationData['annotation'];
                $reflectionMethod = new \ReflectionMethod($service, $annotationData['method']);

                switch(\get_class($annotation))
                {
                    case CommandHandler::class:
                    case EventHandler::class:

                        $list['messages'][] = $this->extractMessageHandlers(
                            $service, $reflectionMethod, $annotation, $defaultServiceLoggerChannel
                        );

                        break;

                    case ErrorHandler::class:

                        $errorHandler = $this->extractErrorHandlers($service, $reflectionMethod);

                        $list['errors'][] = $errorHandler;

                        /** Add parent exception classes */
                        foreach(\class_parents($errorHandler['exceptionClass']) as $parentException)
                        {
                            $list['errors'][] = [
                                'exceptionClass' => $parentException,
                                'forMessage'     => $errorHandler['forMessage'],
                                'handler'        => $errorHandler['handler']
                            ];
                        }

                        break;
                }
            }
            catch(\Throwable $throwable)
            {
                $this->logger->error(ThrowableFormatter::toString($throwable));
            }
        }

        return $list;
    }

    /**
     * Extract error handler
     *
     * @param ServiceInterface  $service
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return array
     */
    private function extractErrorHandlers(
        ServiceInterface $service,
        \ReflectionMethod $reflectionMethod
    )
    {
        self::guardNumberOfParametersValid($service, $reflectionMethod, 3);

        /** @var \ReflectionParameter[] $parameters */
        $parameters = $reflectionMethod->getParameters();

        if(
            null === $parameters[0]->getClass() ||
            (
                false === $parameters[0]->getClass()->isSubclassOf(\Exception::class) &&
                \Exception::class !== $parameters[0]->getClass()->getName()
            )
        )
        {
            throw new \LogicException(
                \sprintf(
                    'The first argument to the handler "%s:%s" must be instanceof the "%s"',
                    \get_class($service), $reflectionMethod->getName(), \Exception::class
                )
            );
        }

        self::guardValidMessageArgument($service, $reflectionMethod, $parameters[1], 1);
        self::guardContextValidArgument($service, $reflectionMethod, $parameters[2]);

        return [
            'exceptionClass' => $parameters[0]->getClass()->getName(),
            'forMessage'     => $parameters[1]->getClass()->getName(),
            'handler'        => $reflectionMethod->getClosure($service)
        ];
    }

    /**
     * Extract message (command/events) handler
     *
     * @param ServiceInterface                               $service
     * @param \ReflectionMethod                              $reflectionMethod
     * @param AbstractAnnotation|CommandHandler|EventHandler $annotation
     * @param string|null                                    $defaultServiceLoggerChannel
     *
     * @return array
     */
    private function extractMessageHandlers(
        ServiceInterface $service,
        \ReflectionMethod $reflectionMethod,
        AbstractAnnotation $annotation,
        string $defaultServiceLoggerChannel = null
    ): array
    {
        /** @var \ReflectionParameter[] $parameters */
        $parameters = $reflectionMethod->getParameters();

        self::guardNumberOfParametersValid($service, $reflectionMethod, 2);
        self::guardValidMessageArgument($service, $reflectionMethod, $parameters[0], 0);
        self::guardContextValidArgument($service, $reflectionMethod, $parameters[1]);

        $isEvent = $parameters[0]
            ->getClass()
            ->implementsInterface(EventInterface::class);

        /** @var EventOptions|CommandOptions $options */
        $options = true === $isEvent
            /** @var EventHandler $annotation */
            ? new EventOptions(
                (bool) $annotation->logPayload,
                $defaultServiceLoggerChannel
            )
            /** @var CommandHandler $annotation */
            : new CommandOptions(
                (float) $annotation->retryDelay,
                (int) $annotation->retryCount,
                (bool) $annotation->logPayload,
                $defaultServiceLoggerChannel
            );

        return [
            'forMessage' => $parameters[0]->getClass()->getName(),
            'handler'    => $reflectionMethod->getClosure($service),
            'options'    => $options
        ];
    }

    /**
     * Assert message type is correct
     *
     * @param ServiceInterface     $service
     * @param \ReflectionMethod    $reflectionMethod
     * @param \ReflectionParameter $parameter
     * @param int                  $argumentPosition
     *
     * @return void
     *
     * @throws \LogicException
     */
    private static function guardValidMessageArgument(
        ServiceInterface $service,
        \ReflectionMethod $reflectionMethod,
        \ReflectionParameter $parameter,
        int $argumentPosition
    ): void
    {
        if(
            null === $parameter->getClass() ||
            false === $parameter->getClass()->implementsInterface(MessageInterface::class)
        )
        {
            throw new \LogicException(
                \sprintf(
                    'The %d argument to the handler "%s:%s" must be instanceof the "%s" (%s specified)',
                    $argumentPosition,
                    \get_class($service),
                    $reflectionMethod->getName(),
                    MessageInterface::class,
                    null !== $parameter->getClass()
                        ? $parameter->getClass()->getName()
                        : 'n/a'
                )
            );
        }
    }

    /**
     * Assert arguments count valid
     *
     * @param ServiceInterface  $service
     * @param \ReflectionMethod $reflectionMethod
     * @param int               $expectedParametersCount
     *
     * @return void
     */
    private static function guardNumberOfParametersValid(
        ServiceInterface $service,
        \ReflectionMethod $reflectionMethod,
        int $expectedParametersCount
    ): void
    {
        if($expectedParametersCount !== $reflectionMethod->getNumberOfRequiredParameters())
        {
            throw new \LogicException(
                sprintf(
                    'The handler "%s:%s" contains an invalid number of arguments. Expected count: %d',
                    \get_class($service), $reflectionMethod->getName(), $expectedParametersCount
                )
            );
        }
    }

    /**
     * Assert context argument is valid
     *
     * @param ServiceInterface     $service
     * @param \ReflectionMethod    $reflectionMethod
     * @param \ReflectionParameter $parameter
     *
     * @return void
     *
     * @throws \LogicException
     */
    private static function guardContextValidArgument(
        ServiceInterface $service,
        \ReflectionMethod $reflectionMethod,
        \ReflectionParameter $parameter
    )
    {
        if(
            null === $parameter->getClass() ||
            (
                false === $parameter->getClass()->isSubclassOf(KernelContext::class) &&
                KernelContext::class !== $parameter->getClass()->getName()
            )
        )
        {
            throw new \LogicException(
                \sprintf(
                    'The second argument to the handler "%s:%s" must be instanceof the "%s"',
                    \get_class($service), $reflectionMethod->getName(), KernelContext::class
                )
            );
        }
    }
}
