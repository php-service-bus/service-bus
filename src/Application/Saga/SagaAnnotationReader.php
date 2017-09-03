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

namespace Desperado\Framework\Application\Saga;

use Desperado\Framework\Application\Saga\Exceptions\SagaAnnotationException;
use Desperado\Framework\Common\Formatter\ThrowableFormatter;
use Desperado\Framework\Domain\Annotation\AbstractAnnotation;
use Desperado\Framework\Domain\Messages\EventInterface;
use Desperado\Framework\Infrastructure\Bridge\Annotation\AnnotationReader;
use Desperado\Framework\Infrastructure\EventSourcing\Annotation\Saga;
use Desperado\Framework\Infrastructure\EventSourcing\Annotation\SagaListener;
use Psr\Log\LoggerInterface;

/**
 * Annotation reader for saga
 */
class SagaAnnotationReader
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
     * Extract event listeners annotation data
     *
     * @param string $sagaNamespace
     *
     * @return array
     */
    public function extractEventListenersAnnotation(string $sagaNamespace): array
    {
        $list = [];
        $annotations = $this->annotationReader->loadClassMethodsAnnotation($sagaNamespace);

        foreach($annotations as $annotationData)
        {
            try
            {
                /** @var AbstractAnnotation $annotation */
                $annotation = $annotationData['annotation'];

                self::guardAnnotationType($annotation, $sagaNamespace);

                /** @var SagaListener $annotation */

                self::guardFirstArgumentIsEvent($sagaNamespace, $annotationData['arguments']);

                $list[] = $annotationData;
            }
            catch(\Throwable $throwable)
            {
                $this->logger->error(ThrowableFormatter::toString($throwable));
            }
        }

        return $list;
    }

    /**
     * Extract Saga annotation
     *
     * @param string $saga
     *
     * @return Saga
     */
    public function extractHeaderDeclaration(string $saga): Saga
    {
        $supported = [Saga::class];

        $annotations = \array_filter(
            \array_map(
                function(AbstractAnnotation $annotation) use ($supported)
                {
                    return true === \in_array(\get_class($annotation), $supported, true) ? $annotation : null;
                },
                $this->annotationReader->loadClassAnnotations($saga)
            )
        );

        $annotation = \end($annotations);

        return $annotation;
    }

    /**
     * Assert correct annotation type
     *
     * @param AbstractAnnotation $annotation
     * @param string             $saga
     *
     * @return void
     *
     * @throws SagaAnnotationException
     */
    private static function guardAnnotationType(AbstractAnnotation $annotation, string $saga): void
    {
        if(false === ($annotation instanceof SagaListener))
        {
            throw new SagaAnnotationException(
                \sprintf('Unsupported annotation specified ("%s") for saga "%s"', \get_class($annotation), $saga)
            );
        }
    }

    /**
     * Assert correct handler arguments
     *
     * @param string                 $saga
     * @param \ReflectionParameter[] $parameters
     *
     * @return void
     *
     * @throws SagaAnnotationException
     */
    private static function guardFirstArgumentIsEvent(string $saga, array $parameters): void
    {
        if(
            false === isset($parameters[0]) ||
            null === $parameters[0]->getClass() ||
            false === $parameters[0]->getClass()->implementsInterface(EventInterface::class)
        )
        {
            throw new SagaAnnotationException(
                \sprintf(
                    'The event handler for the saga "%s" should take the first argument to the object '
                    . 'that implements the "%s" interface',
                    $saga, EventInterface::class
                )
            );
        }
    }
}
