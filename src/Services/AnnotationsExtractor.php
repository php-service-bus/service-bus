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
use Desperado\ServiceBus\Services\Handlers;
use Desperado\ServiceBus\Services\Configuration\ConfigurationGuard;
use Desperado\ServiceBus\Services\Exceptions as ServicesExceptions;

/**
 * Annotation-based handlers extractor
 */
class AnnotationsExtractor implements ServiceHandlersExtractorInterface
{
    /**
     * Annotation reader
     *
     * @var Bridge\AnnotationsReader\AnnotationsReaderInterface
     */
    private $annotationReader;

    /**
     * @param Bridge\AnnotationsReader\AnnotationsReaderInterface $annotationReader
     */
    public function __construct(Bridge\AnnotationsReader\AnnotationsReaderInterface $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * @inheritdoc
     */
    public function extractServiceLoggerChannel(ServiceInterface $service): string
    {
        $supportedList = [Annotations\Service::class];
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

        if(true === \is_object($annotation) && $annotation instanceof Annotations\Service)
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
    ): array
    {
        $messageHandlers = Handlers\Messages\MessageHandlersCollection::create();
        $exceptionHandlers = Handlers\Exceptions\ExceptionHandlersCollection::create();

        $messageHandlerAnnotations = $this->annotationReader->loadClassMethodsAnnotation($service);

        foreach($messageHandlerAnnotations as $annotationData)
        {
            /** @var Bridge\AnnotationsReader\MethodAnnotation $annotationData */

            switch(\get_class($annotationData->getAnnotation()))
            {
                case Annotations\CommandHandler::class:
                case Annotations\EventHandler::class:

                    $messageHandlers->add(
                        $this->extractMessageHandler($service, $annotationData, $defaultServiceLoggerChannel)
                    );

                    break;

                case Annotations\ErrorHandler::class:

                    /** @var Handlers\Exceptions\ExceptionHandlerData $originErrorHandlerData */
                    $originErrorHandlerData = $this->extractErrorHandlers($service, $annotationData->getMethod());

                    $exceptionHandlers->add($originErrorHandlerData);

                    /** Add parent exception classes */
                    foreach(\class_parents($originErrorHandlerData->getExceptionClassNamespace()) as $parentException)
                    {
                        $exceptionHandlers->add(
                            Handlers\Exceptions\ExceptionHandlerData::new(
                                $parentException,
                                $originErrorHandlerData->getMessageClassNamespace(),
                                $originErrorHandlerData->getExceptionHandler(),
                                $originErrorHandlerData->getExceptionHandlingParameters()
                            )
                        );
                    }

                    break;
            }
        }

        return [
            self::HANDLER_TYPE_MESSAGES => $messageHandlers,
            self::HANDLER_TYPE_ERRORS   => $exceptionHandlers
        ];
    }

    /**
     * Extract error handler
     *
     * @param ServiceInterface  $service
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return Handlers\Exceptions\ExceptionHandlerData
     *
     * @throws ServicesExceptions\ServiceConfigurationExceptionInterface
     */
    private function extractErrorHandlers(
        ServiceInterface $service,
        \ReflectionMethod $reflectionMethod
    ): Handlers\Exceptions\ExceptionHandlerData
    {
        ConfigurationGuard::guardNumberOfParametersValid($reflectionMethod, 3);

        /** @var \ReflectionParameter[] $parameters */
        $parameters = $reflectionMethod->getParameters();

        ConfigurationGuard::guardValidThrowableArgument(
            $reflectionMethod,
            $reflectionMethod->getParameters()[0]
        );

        ConfigurationGuard::guardValidMessageArgument($reflectionMethod, $parameters[1], 1);
        ConfigurationGuard::guardContextValidArgument($reflectionMethod, $parameters[2]);

        return Handlers\Exceptions\ExceptionHandlerData::new(
            $parameters[0]->getClass()->getName(),
            $parameters[1]->getClass()->getName(),
            $reflectionMethod->getClosure($service),
            new Handlers\Exceptions\ExceptionHandlingParameters()
        );
    }

    /**
     * Extract message (command/events) handler
     *
     * @param ServiceInterface                          $service
     * @param Bridge\AnnotationsReader\MethodAnnotation $methodAnnotation
     * @param string|null                               $defaultServiceLoggerChannel
     *
     * @return Handlers\Messages\MessageHandlerData
     *
     * @throws ServicesExceptions\ServiceConfigurationExceptionInterface
     */
    private function extractMessageHandler(
        ServiceInterface $service,
        Bridge\AnnotationsReader\MethodAnnotation $methodAnnotation,
        string $defaultServiceLoggerChannel = null
    ): Handlers\Messages\MessageHandlerData
    {
        ConfigurationGuard::guardNumberOfParametersValid($methodAnnotation->getMethod(), 2);

        ConfigurationGuard::guardValidMessageArgument(
            $methodAnnotation->getMethod(),
            $methodAnnotation->getArguments()[0],
            0
        );

        ConfigurationGuard::guardContextValidArgument(
            $methodAnnotation->getMethod(),
            $methodAnnotation->getArguments()[1]
        );

        $isEvent = $methodAnnotation->getArguments()[0]
            ->getClass()
            ->isSubclassOf(AbstractEvent::class);

        /** @var Annotations\CommandHandler|Annotations\EventHandler $annotation */
        $annotation = $methodAnnotation->getAnnotation();

        $loggerChannel = '' !== (string) $annotation->getLoggerChannel()
            ? $annotation->getLoggerChannel()
            : $defaultServiceLoggerChannel;

        $options = true === $isEvent
            ? new Handlers\Messages\EventExecutionParameters((string) $loggerChannel)
            : new Handlers\Messages\CommandExecutionParameters((string) $loggerChannel);

        return Handlers\Messages\MessageHandlerData::new(
            $methodAnnotation->getArguments()[0]->getClass()->getName(),
            $methodAnnotation->getMethod()->getClosure($service),
            $options
        );
    }
}
