<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services;

use Desperado\Infrastructure\Bridge;
use Desperado\ServiceBus\Annotations;
use Desperado\ServiceBus\ServiceInterface;
use Desperado\ServiceBus\Services\Handlers;
use Desperado\ServiceBus\Services\Configuration\ConfigurationGuard;
use Desperado\ServiceBus\Services\Exceptions as ServicesExceptions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Annotation-based handlers extractor
 */
final class AnnotationsExtractor implements ServiceHandlersExtractorInterface
{
    private const SUPPORTED_CLASS_ANNOTATIONS = [
        Annotations\Services\Service::class
    ];

    private const SUPPORTED_METHOD_ANNOTATIONS = [
        Annotations\Services\CommandHandler::class,
        Annotations\Services\EventHandler::class,
        Annotations\Services\QueryHandler::class
    ];

    /**
     * Annotation reader
     *
     * @var Bridge\AnnotationsReader\AnnotationsReaderInterface
     */
    private $annotationReader;

    /**
     * Search for services to be substituted as arguments to the handler
     *
     * @var AutowiringServiceLocator
     */
    private $autowiringServiceLocator;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Http requests router
     *
     * @var Bridge\Router\RouterInterface
     */
    private $router;

    /**
     * @param Bridge\AnnotationsReader\AnnotationsReaderInterface $annotationReader
     * @param AutowiringServiceLocator                            $autowiringServiceLocator
     * @param Bridge\Router\RouterInterface                       $router
     * @param LoggerInterface                                     $logger
     */
    public function __construct(
        Bridge\AnnotationsReader\AnnotationsReaderInterface $annotationReader,
        AutowiringServiceLocator $autowiringServiceLocator,
        Bridge\Router\RouterInterface $router,
        LoggerInterface $logger
    )
    {
        $this->annotationReader = $annotationReader;
        $this->autowiringServiceLocator = $autowiringServiceLocator;
        $this->router = $router;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function extractServiceLoggerChannel(ServiceInterface $service): string
    {
        $annotations = \array_filter(
            \array_map(
                function(Bridge\AnnotationsReader\ClassAnnotation $annotation)
                {
                    return true === \in_array($annotation->getClass(), self::SUPPORTED_CLASS_ANNOTATIONS, true)
                        ? $annotation->getAnnotation()
                        : null;
                },
                \iterator_to_array($this->annotationReader->loadClassAnnotations($service))
            )
        );

        $annotation = \end($annotations);

        if(true === \is_object($annotation) && $annotation instanceof Annotations\Services\Service)
        {
            return $annotation->getLoggerChannel();
        }

        throw new ServicesExceptions\ServiceClassAnnotationNotFoundException(\get_class($service));
    }

    /**
     * @inheritdoc
     */
    public function extractHandlers(
        ServiceInterface $service,
        string $defaultServiceLoggerChannel = null
    ): Handlers\MessageHandlersCollection
    {
        $messageHandlers = Handlers\MessageHandlersCollection::create();
        $messageHandlerAnnotations = $this->annotationReader->loadClassMethodsAnnotation($service);

        foreach($messageHandlerAnnotations as $annotationData)
        {
            /** @var Bridge\AnnotationsReader\MethodAnnotation $annotationData */

            ConfigurationGuard::guardHandlerReturnDeclaration($annotationData->getMethod());

            $annotationClass = \get_class($annotationData->getAnnotation());

            if(false === \in_array($annotationClass, self::SUPPORTED_METHOD_ANNOTATIONS, true))
            {
                /** Most likely this is some kind of user annotation. We will not throw an error */

                $this->logger->debug(
                    \sprintf(
                        'An unsupported annotation ("%s") was found in method "%s:%s"',
                        $annotationClass,
                        $annotationData->getMethod()->getDeclaringClass()->getName(),
                        $annotationData->getMethod()->getName()
                    )
                );

                continue;
            }

            $messageHandlers->add(
                $this->extractMessageHandler($service, $annotationData, $defaultServiceLoggerChannel)
            );
        }

        return $messageHandlers;
    }

    /**
     * Extract message (command/events) handler
     *
     * @param ServiceInterface                          $service
     * @param Bridge\AnnotationsReader\MethodAnnotation $methodAnnotation
     * @param string|null                               $defaultServiceLoggerChannel
     *
     * @return Handlers\MessageHandlerData
     *
     * @throws ServicesExceptions\ServiceConfigurationExceptionInterface
     */
    private function extractMessageHandler(
        ServiceInterface $service,
        Bridge\AnnotationsReader\MethodAnnotation $methodAnnotation,
        string $defaultServiceLoggerChannel = null
    ): Handlers\MessageHandlerData
    {
        $reflectionMethod = $methodAnnotation->getMethod();
        $methodArguments = $methodAnnotation->getArguments();

        ConfigurationGuard::guardMessageHandlerNumberOfParametersValid($reflectionMethod);
        ConfigurationGuard::guardValidMessageArgument($reflectionMethod, $methodArguments[0], 0);
        ConfigurationGuard::guardContextValidArgument($reflectionMethod, $methodArguments[1]);

        $autowiringServices = $this->collectAutowiringServices($reflectionMethod, 2);
        /** @var Annotations\Services\MessageHandlerAnnotationInterface $annotation */
        $annotation = $methodAnnotation->getAnnotation();

        $handler = Handlers\MessageHandlerData::new(
            $methodAnnotation->getArguments()[0]->getClass()->getName(),
            $methodAnnotation->getMethod()->getClosure($service),
            $autowiringServices,
            $this->createMessageOptions($annotation, (string) $defaultServiceLoggerChannel)
        );

        if($annotation instanceof Annotations\Services\HttpHandlerAnnotationInterface)
        {
            $this->configureRouter($handler, $methodAnnotation);
        }

        return $handler;
    }

    /**
     * Configure http routes
     *
     * @param Handlers\MessageHandlerData               $handler
     * @param Bridge\AnnotationsReader\MethodAnnotation $methodAnnotation
     *
     * @return void
     *
     * @throws ServicesExceptions\IncorrectMessageTypeException
     * @throws ServicesExceptions\IncorrectHttpMethodException
     */
    private function configureRouter(
        Handlers\MessageHandlerData $handler,
        Bridge\AnnotationsReader\MethodAnnotation $methodAnnotation
    ): void
    {
        /** @var Annotations\Services\HttpHandlerAnnotationInterface $annotation */
        $annotation = $methodAnnotation->getAnnotation();

        /** not required */
        if('' === (string) $annotation->getRoute() || '' === (string) $annotation->getMethod())
        {
            return;
        }

        $handlerPath = \sprintf(
            '%s:%s',
            $methodAnnotation->getMethod()->getDeclaringClass()->getName(),
            $methodAnnotation->getMethod()->getName()
        );

        ConfigurationGuard::guardHttpMethod($handlerPath, $annotation->getMethod());
        ConfigurationGuard::guardHttpMessageType($handlerPath, $handler->getMessageClassNamespace());

        $this->logger->debug(
            \sprintf('Added http route "[%s] %s"', $annotation->getMethod(), $annotation->getRoute())
        );

        $this->router->addRoute(
            $annotation->getRoute(),
            $annotation->getMethod(),
            \Closure::fromCallable(
                function() use ($handler)
                {
                    return $handler;
                }
            )
        );
    }

    /**
     * Create execution options object for message
     *
     * @param Annotations\Services\MessageHandlerAnnotationInterface $annotation
     * @param string                                                 $defaultServiceLoggerChannel
     *
     * @return Handlers\AbstractMessageExecutionParameters
     *
     * @throws \LogicException
     */
    private function createMessageOptions(
        Annotations\Services\MessageHandlerAnnotationInterface $annotation,
        string $defaultServiceLoggerChannel
    ): Handlers\AbstractMessageExecutionParameters
    {
        $annotationClass = \get_class($annotation);
        $loggerChannel = '' !== (string) $annotation->getLoggerChannel()
            ? (string) $annotation->getLoggerChannel()
            : (string) $defaultServiceLoggerChannel;

        switch($annotationClass)
        {
            /** @var Annotations\Services\CommandHandler $annotation */
            case Annotations\Services\CommandHandler::class:
                return new Handlers\CommandExecutionParameters($loggerChannel);

            case Annotations\Services\EventHandler::class:
                /** @var Annotations\Services\EventHandler $annotation */
                return new Handlers\EventExecutionParameters($loggerChannel);

            case Annotations\Services\QueryHandler::class:
                /** @var Annotations\Services\QueryHandler $annotation */
                return new Handlers\QueryExecutionParameters($loggerChannel);

            // @codeCoverageIgnoreStart
            default:
                throw new \LogicException(
                    \sprintf('Unsupported annotation type ("%s")', $annotationClass)
                );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Collecting additional automatically substituted arguments
     *
     * @param \ReflectionMethod $reflectionMethod
     * @param int               $fromArgIndex
     *
     * @return array
     *
     * @throws ServicesExceptions\InvalidHandlerArgumentException
     */
    private function collectAutowiringServices(\ReflectionMethod $reflectionMethod, int $fromArgIndex): array
    {
        $result = [];

        foreach($reflectionMethod->getParameters() as $index => $reflectionParameter)
        {
            if($fromArgIndex <= $index)
            {
                $result[] = $this->extractAutowiringService(
                    $reflectionMethod,
                    $reflectionParameter,
                    $index
                );
            }
        }

        return $result;
    }

    /**
     * Get the object of the automatically provided service
     *
     * @param \ReflectionMethod    $reflectionMethod
     * @param \ReflectionParameter $reflectionParameter
     * @param int                  $index
     *
     * @return object
     *
     * @throws ServicesExceptions\InvalidHandlerArgumentException
     */
    private function extractAutowiringService(
        \ReflectionMethod $reflectionMethod,
        \ReflectionParameter $reflectionParameter,
        int $index
    )
    {
        if(null !== $reflectionParameter->getClass())
        {
            return $this->locateService($reflectionParameter->getClass()->getName());
        }

        throw new ServicesExceptions\InvalidHandlerArgumentException(
            \sprintf('The %d argument to the handler "%s:%s" should be of the type "object"',
                $index,
                $reflectionMethod->getDeclaringClass()->getName(),
                $reflectionMethod->getName()

            )
        );
    }

    /**
     * Locate service in container by it class
     *
     * @param string $serviceClass
     *
     * @return object
     *
     * @throws ServicesExceptions\InvalidHandlerArgumentException
     */
    private function locateService(string $serviceClass)
    {
        try
        {
            $this->assertServiceExists($serviceClass);

            return $this->autowiringServiceLocator->get($serviceClass);
        }
        catch(ContainerExceptionInterface $exception)
        {
            throw new ServicesExceptions\InvalidHandlerArgumentException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Checking the existence of the service in the container
     *
     * @param string $serviceClass
     *
     * @return void
     *
     * @throws ServicesExceptions\InvalidHandlerArgumentException
     */
    private function assertServiceExists(string $serviceClass)
    {
        if(false === $this->autowiringServiceLocator->has($serviceClass))
        {
            throw new ServicesExceptions\InvalidHandlerArgumentException(
                \sprintf('The service for the specified class ("%s") was not described in the dependency container',
                    $serviceClass
                )
            );
        }
    }
}
