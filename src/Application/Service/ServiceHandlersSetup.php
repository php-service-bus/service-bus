<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Application\Service;

use Desperado\Framework\Domain\Service\ServiceInterface;
use Desperado\Framework\Infrastructure\Bridge\Annotation\AnnotationReader;
use Desperado\Framework\Infrastructure\CQRS\Annotation\Service;
use Desperado\Framework\Infrastructure\CQRS\MessageBus\MessageBusBuilder;
use Psr\Log\LoggerInterface;

/**
 * Service handlers setup
 */
class ServiceHandlersSetup
{
    /**
     * Message bus builder
     *
     * @var MessageBusBuilder
     */
    private $messageBusBuilder;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Service annotations reader
     *
     * @var ServiceAnnotationReader
     */
    private $serviceAnnotationsReader;

    /**
     * @param MessageBusBuilder $messageBusBuilder
     * @param AnnotationReader  $annotationReader
     * @param LoggerInterface   $logger
     */
    public function __construct(
        MessageBusBuilder $messageBusBuilder,
        AnnotationReader $annotationReader,
        LoggerInterface $logger
    )
    {
        $this->messageBusBuilder = $messageBusBuilder;
        $this->logger = $logger;

        $this->serviceAnnotationsReader = new ServiceAnnotationReader($annotationReader, $logger);
    }

    /**
     * Setup service handlers
     *
     * @param ServiceInterface $service
     *
     * @return void
     */
    public function setup(ServiceInterface $service)
    {
        $serviceNamespace = \get_class($service);
        $headersAnnotations = $this->serviceAnnotationsReader->extractHeaderAnnotations(
            $service, [Service::class]
        );

        /** @var Service|null $serviceAnnotation */
        $serviceAnnotation = \end($headersAnnotations);

        $globalLogChannel = null !== $serviceAnnotation ? $serviceAnnotation->loggerChannel : null;

        $handlers = $this->serviceAnnotationsReader->extractHandlers($service, $globalLogChannel);

        foreach($handlers as $type => $collection)
        {
            foreach($collection as $handlerData)
            {
                switch($type)
                {
                    case 'messages':

                        $this->messageBusBuilder->addMessageHandler(
                            $handlerData['forMessage'],
                            $handlerData['handler'],
                            $handlerData['options']
                        );

                        $this->logger->debug(
                            \sprintf('Added a message handler for "%s:%s"', $serviceNamespace, $handlerData['forMessage'])
                        );

                        break;

                    case 'errors';

                        $this->messageBusBuilder->addErrorHandler(
                            $handlerData['exceptionClass'],
                            $handlerData['forMessage'],
                            $handlerData['handler']
                        );

                        $this->logger->debug(
                            \sprintf(
                                'An exception handler (type "%s") has been added for "%s:%s"',
                                $handlerData['exceptionClass'], $serviceNamespace, $handlerData['forMessage']
                            )
                        );

                        break;

                    default:
                        throw new \LogicException(\sprintf('Unsupported handler type specified', $type));
                }
            }
        }
    }
}
