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

use Desperado\Domain\Message\AbstractEvent;
use Desperado\Infrastructure\Bridge;
use Desperado\ServiceBus\Annotations;
use Desperado\ServiceBus\ServiceInterface;
use Desperado\ServiceBus\Services\Handlers;
use Desperado\ServiceBus\Services\Configuration\ConfigurationGuard;
use Desperado\ServiceBus\Services\Exceptions as ServicesExceptions;
use Psr\Container\ContainerExceptionInterface;

/**
 * Annotation-based handlers extractor
 */
final class AnnotationsExtractor implements ServiceHandlersExtractorInterface
{
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
     * @param Bridge\AnnotationsReader\AnnotationsReaderInterface $annotationReader
     * @param AutowiringServiceLocator                            $autowiringServiceLocator
     */
    public function __construct(
        Bridge\AnnotationsReader\AnnotationsReaderInterface $annotationReader,
        AutowiringServiceLocator $autowiringServiceLocator
    )
    {
        $this->annotationReader = $annotationReader;
        $this->autowiringServiceLocator = $autowiringServiceLocator;
    }

    /**
     * @inheritdoc
     */
    public function extractServiceLoggerChannel(ServiceInterface $service): string
    {
        $supportedList = [Annotations\Services\Service::class];
        $annotations = \array_filter(
            \array_map(
                function(Bridge\AnnotationsReader\ClassAnnotation $annotation) use ($supportedList)
                {
                    return true === \in_array($annotation->getClass(), $supportedList, true)
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

            /** @todo: query handlers */

            switch(\get_class($annotationData->getAnnotation()))
            {
                case Annotations\Services\CommandHandler::class:
                case Annotations\Services\EventHandler::class:

                    $messageHandlers->add(
                        $this->extractMessageHandler($service, $annotationData, $defaultServiceLoggerChannel)
                    );

                    break;
            }
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

        $isEvent = $methodArguments[0]
            ->getClass()
            ->isSubclassOf(AbstractEvent::class);

        /** @var Annotations\Services\CommandHandler|Annotations\Services\EventHandler $annotation */
        $annotation = $methodAnnotation->getAnnotation();

        $loggerChannel = '' !== (string) $annotation->getLoggerChannel()
            ? $annotation->getLoggerChannel()
            : $defaultServiceLoggerChannel;

        $options = true === $isEvent
            ? new Handlers\EventExecutionParameters((string) $loggerChannel)
            : new Handlers\CommandExecutionParameters((string) $loggerChannel);

        return Handlers\MessageHandlerData::new(
            $methodAnnotation->getArguments()[0]->getClass()->getName(),
            $methodAnnotation->getMethod()->getClosure($service),
            $autowiringServices,
            $options
        );
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
        $reflectionClass = $reflectionParameter->getClass();

        $baseMessagePart = \sprintf(
            'The %d argument to the handler "%s:%s"',
            $index,
            $reflectionMethod->getDeclaringClass()->getName(),
            $reflectionMethod->getName()
        );

        if(null !== $reflectionClass)
        {
            if(false === $this->autowiringServiceLocator->has($reflectionClass->getName()))
            {
                throw new ServicesExceptions\InvalidHandlerArgumentException(
                    \sprintf(
                        '%s not specified correctly. The service for the specified class ("%s") was not '
                        . 'described in the dependency container',
                        $baseMessagePart,
                        $reflectionClass->getName()
                    )
                );
            }

            try
            {
                return $this->autowiringServiceLocator->get($reflectionClass->getName());
            }
            catch(ContainerExceptionInterface $exception)
            {
                throw new ServicesExceptions\InvalidHandlerArgumentException($exception->getMessage());
            }
        }
        else
        {
            throw new ServicesExceptions\InvalidHandlerArgumentException(
                \sprintf('%s should be of the type "object"', $baseMessagePart)
            );
        }
    }
}
