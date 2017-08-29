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
use Desperado\ConcurrencyFramework\Domain\Messages\CommandInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Domain\Service\ServiceInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Annotation\AnnotationReader;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\AbstractExecutionOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\CommandOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\ErrorOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\EventOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\MessageBus\MessageBusBuilder;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Annotation;
use Psr\Log\LoggerInterface;

/**
 * Service configurator
 */
class ServiceConfigurator
{
    /**
     * Message bus builder
     *
     * @var MessageBusBuilder
     */
    private $messageBusBuilder;

    /**
     * Annotations reader
     *
     * @var AnnotationReader
     */
    private $annotationsReader;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param MessageBusBuilder        $messageBusBuilder
     * @param AnnotationReader         $annotationsReader
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        MessageBusBuilder $messageBusBuilder,
        AnnotationReader $annotationsReader,
        LoggerInterface $logger
    )
    {
        $this->messageBusBuilder = $messageBusBuilder;
        $this->annotationsReader = $annotationsReader;
        $this->logger = $logger;
    }

    /**
     * Extract all message handlers
     *
     * @param ServiceInterface $service
     *
     * @return void
     */
    public function extract(ServiceInterface $service): void
    {
        $globalLoggerChannel = $this->extractGlobalLoggerChannel($service);

        $messageHandlers = $this->annotationsReader->loadClassMethodsAnnotation($service);

        foreach($messageHandlers as $annotationData)
        {
            /** @var AbstractAnnotation $annotation */
            $annotation = $annotationData['annotation'];
            $reflectionMethod = new \ReflectionMethod($service, $annotationData['method']);

            switch(\get_class($annotation))
            {
                case Annotation\Service\CommandHandlerAnnotation::class:

                    /** @var Annotation\Service\CommandHandlerAnnotation $annotation */

                    $this->configureCommandHandler(
                        $service, $annotation, $reflectionMethod, $globalLoggerChannel
                    );

                    break;

                case Annotation\Service\EventListenerAnnotation::class:

                    /** @var Annotation\Service\EventListenerAnnotation $annotation */

                    $this->configureEventHandler(
                        $service, $annotation, $reflectionMethod, $globalLoggerChannel
                    );

                    break;

                case Annotation\Service\ErrorHandlerAnnotation::class:

                    /** @var Annotation\Service\ErrorHandlerAnnotation $annotation */

                    $this->configureErrorHandler(
                        $service, $annotation, $reflectionMethod, $globalLoggerChannel
                    );

                    break;
            }
        }
    }

    /**
     * Configure error handler
     *
     * @param ServiceInterface                          $service
     * @param Annotation\Service\ErrorHandlerAnnotation $annotation
     * @param \ReflectionMethod                         $reflectionMethod
     * @param string                                    $globalLoggerChannel
     *
     * @return void
     */
    private function configureErrorHandler(
        ServiceInterface $service,
        Annotation\Service\ErrorHandlerAnnotation $annotation,
        \ReflectionMethod $reflectionMethod,
        string $globalLoggerChannel
    )
    {
        try
        {
            $this->assertNumberOfParametersValid($service, $reflectionMethod, 3);

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

            $this->assertValidMessageArgument(
                $service, $reflectionMethod, $parameters[1], CommandInterface::class
            );

            $this->assertContextValidArgument($service, $reflectionMethod, $parameters[2]);

            $options = new ErrorOptions(
                self::extractHandlerLoggerChannel($annotation, $globalLoggerChannel)
            );

            $this->messageBusBuilder->addErrorHandler(
                $parameters[1]->getClass()->getName(),
                $reflectionMethod->getClosure($service),
                $options
            );

            $this->logSuccessfulHandlerRegister(
                \get_class($service), $reflectionMethod->getName(), $options
            );
        }
        catch(\LogicException $exception)
        {
            $this->logger->error(ThrowableFormatter::toString($exception));
        }
    }

    /**
     * Register event handler
     *
     * @param ServiceInterface                           $service
     * @param Annotation\Service\EventListenerAnnotation $annotation
     * @param \ReflectionMethod                          $reflectionMethod
     * @param string                                     $globalLoggerChannel
     *
     * @return void
     */
    private function configureEventHandler(
        ServiceInterface $service,
        Annotation\Service\EventListenerAnnotation $annotation,
        \ReflectionMethod $reflectionMethod,
        string $globalLoggerChannel
    ): void
    {
        try
        {
            $this->assertNumberOfParametersValid($service, $reflectionMethod, 2);

            /** @var \ReflectionParameter[] $parameters */
            $parameters = $reflectionMethod->getParameters();

            $this->assertValidMessageArgument(
                $service, $reflectionMethod, $parameters[0], EventInterface::class
            );

            $this->assertContextValidArgument($service, $reflectionMethod, $parameters[1]);

            $options = new EventOptions(
                $annotation->logPayload,
                self::extractHandlerLoggerChannel($annotation, $globalLoggerChannel)
            );

            $this->messageBusBuilder->addEventHandler(
                $reflectionMethod->getClosure($service),
                $options
            );

            $this->logSuccessfulHandlerRegister(
                \get_class($service), $reflectionMethod->getName(), $options
            );
        }
        catch(\LogicException $exception)
        {
            $this->logger->error(ThrowableFormatter::toString($exception));
        }
    }

    /**
     * Configure command handler
     *
     * @param ServiceInterface                            $service
     * @param Annotation\Service\CommandHandlerAnnotation $annotation
     * @param \ReflectionMethod                           $reflectionMethod
     * @param string                                      $globalLoggerChannel
     *
     * @return void
     */
    private function configureCommandHandler(
        ServiceInterface $service,
        Annotation\Service\CommandHandlerAnnotation $annotation,
        \ReflectionMethod $reflectionMethod,
        string $globalLoggerChannel
    ): void
    {
        try
        {
            $this->assertNumberOfParametersValid($service, $reflectionMethod, 2);

            /** @var \ReflectionParameter[] $parameters */
            $parameters = $reflectionMethod->getParameters();

            $this->assertValidMessageArgument(
                $service, $reflectionMethod, $parameters[0], CommandInterface::class
            );

            $this->assertContextValidArgument($service, $reflectionMethod, $parameters[1]);

            $options = new CommandOptions(
                (float) $annotation->retryDelay,
                (int) $annotation->retryCount,
                (bool) $annotation->logPayload,
                self::extractHandlerLoggerChannel($annotation, $globalLoggerChannel)
            );

            $this->messageBusBuilder->addCommandHandler(
                $reflectionMethod->getClosure($service),
                $options
            );

            $this->logSuccessfulHandlerRegister(
                \get_class($service), $reflectionMethod->getName(), $options
            );
        }
        catch(\LogicException $exception)
        {
            $this->logger->error(ThrowableFormatter::toString($exception));
        }
    }

    /**
     * Extract global handlers log channel
     *
     * @param ServiceInterface $service
     *
     * @return string
     */
    private function extractGlobalLoggerChannel(ServiceInterface $service)
    {
        $globalServiceLoggerChannel = '';
        $serviceAnnotations = $this->annotationsReader->loadClassAnnotations($service);

        \array_map(
            function(AbstractAnnotation $annotation) use (&$globalServiceLoggerChannel)
            {
                if(
                    $annotation instanceof Annotation\Service\ServiceAnnotation &&
                    '' !== (string) $annotation->loggerChannel
                )
                {
                    $globalServiceLoggerChannel = (string) $annotation->loggerChannel;
                }
            },
            $serviceAnnotations
        );

        return $globalServiceLoggerChannel;
    }

    /**
     * Extract logger channel from annotation
     *
     * @param AbstractAnnotation $annotation
     * @param string             $globalServiceLoggerChannel
     *
     * @return string
     */
    private static function extractHandlerLoggerChannel(
        AbstractAnnotation $annotation,
        string $globalServiceLoggerChannel
    ): string
    {
        if(true === \property_exists($annotation, 'loggerChannel'))
        {
            return '' !== (string) $annotation->loggerChannel
                ? (string) $annotation->loggerChannel
                : $globalServiceLoggerChannel;
        }

        return $globalServiceLoggerChannel;
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
    private function assertNumberOfParametersValid(
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
    private function assertContextValidArgument(
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

    /**
     * Assert message type is correct
     *
     * @param ServiceInterface     $service
     * @param \ReflectionMethod    $reflectionMethod
     * @param \ReflectionParameter $parameter
     * @param string               $expectedInterface
     *
     * @return void
     *
     * @throws \LogicException
     */
    private function assertValidMessageArgument(
        ServiceInterface $service,
        \ReflectionMethod $reflectionMethod,
        \ReflectionParameter $parameter,
        string $expectedInterface
    ): void
    {
        if(
            null === $parameter->getClass() ||
            false === $parameter->getClass()->implementsInterface($expectedInterface)
        )
        {
            throw new \LogicException(
                \sprintf(
                    'The first argument to the handler "%s:%s" must be instanceof the "%s"',
                    \get_class($service), $reflectionMethod->getName(), $expectedInterface
                )
            );
        }
    }

    /**
     * Log successful handler added
     *
     * @param string                   $serviceNamespace
     * @param string                   $method
     * @param AbstractExecutionOptions $options
     *
     * @return void
     */
    private function logSuccessfulHandlerRegister(
        string $serviceNamespace,
        string $method,
        AbstractExecutionOptions $options
    )
    {
        $this->logger->debug(
            \sprintf(
                'Handler for "%s::%s" successful registered with options %s',
                $serviceNamespace, $method, \urldecode(\http_build_query($options->toArray()))
            )
        );
    }
}
