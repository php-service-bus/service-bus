<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Configuration;

use Desperado\Infrastructure\Bridge\AnnotationsReader\AnnotationsReaderInterface;
use Desperado\Infrastructure\Bridge\AnnotationsReader\ClassAnnotation;
use Desperado\Infrastructure\Bridge\AnnotationsReader\DoctrineAnnotationsReader;
use Desperado\Infrastructure\Bridge\AnnotationsReader\MethodAnnotation;
use Desperado\ServiceBus\Annotations\Sagas\Saga;
use Desperado\ServiceBus\Annotations\Sagas\SagaEventListener;
use Desperado\ServiceBus\Saga\Configuration\Exceptions\SagaAnnotationNotAppliedException;
use Desperado\ServiceBus\Saga\Configuration\Guard\SagaConfigurationGuard;

/**
 * Saga configuration (annotation-based) reader
 */
final class AnnotationsSagaConfigurationExtractor implements SagaConfigurationExtractorInterface
{
    /**
     * Annotation reader
     *
     * @var AnnotationsReaderInterface
     */
    private $annotationReader;

    /**
     * Sagas configuration
     *
     * [
     *     'SomeSagaNamespace' => [
     *         0 => 'SomeExpireDateModifier',
     *         1 => 'SomeIdentityClassNamespace',
     *         2 => 'containingIdentifierProperty'
     *     ]
     * ]
     *
     * @var array
     */
    private $sagasConfiguration = [];

    /**
     * Event listeners
     *
     * [
     *     'SomeSagaNamespace' => [
     *         0 => 'SomeEventNamespace',
     *         1 => 'SomeCustomContainingIdentifierProperty'
     *     ]
     * ]
     *
     * @var array
     */
    private $sagasListeners = [];

    /**
     * @param AnnotationsReaderInterface $annotationReader
     */
    public function __construct(AnnotationsReaderInterface $annotationReader = null)
    {
        $this->annotationReader = $annotationReader ?? new DoctrineAnnotationsReader();
    }

    /**
     * @inheritdoc
     */
    public function extractSagaConfiguration(string $sagaNamespace): SagaConfiguration
    {
        if(false === isset($this->sagasConfiguration[$sagaNamespace]))
        {
            $supportedAnnotations = [Saga::class];

            $annotations = \array_filter(
                \array_map(
                    function(ClassAnnotation $classAnnotation) use ($supportedAnnotations)
                    {
                        $annotationClass = $classAnnotation->getAnnotation()->getClass();

                        return true === \in_array($annotationClass, $supportedAnnotations, true)
                            ? $classAnnotation->getAnnotation()
                            : null;
                    },
                    \iterator_to_array($this->annotationReader->loadClassAnnotations($sagaNamespace))
                )
            );

            /** @var Saga|null $annotation */
            $annotation = \end($annotations);

            if($annotation instanceof Saga)
            {
                SagaConfigurationGuard::assertExpireDateIsValid($annotation->getExpireDateModifier());
                SagaConfigurationGuard::assertIdentifierClassNamespaceIsValid((string) $annotation->getIdentifierNamespace());
                SagaConfigurationGuard::assertContainingIdentifierPropertySpecified(
                    (string) $annotation->getContainingIdentifierProperty()
                );

                $this->sagasConfiguration[$sagaNamespace] = SagaConfiguration::create(
                    $sagaNamespace,
                    $annotation->getExpireDateModifier(),
                    $annotation->getIdentifierNamespace(),
                    $annotation->getContainingIdentifierProperty()
                );
            }
            else
            {
                throw new SagaAnnotationNotAppliedException($sagaNamespace);
            }
        }

        return $this->sagasConfiguration[$sagaNamespace];
    }

    /**
     * @inheritdoc
     */
    public function extractSagaListeners(string $sagaNamespace): array
    {
        if(false === isset($this->sagasListeners[$sagaNamespace]))
        {
            $annotations = $this->annotationReader->loadClassMethodsAnnotation($sagaNamespace);

            $this->sagasListeners[$sagaNamespace] = [];

            foreach($annotations as $annotationData)
            {
                /** @var MethodAnnotation $annotationData */

                if($annotationData->getAnnotation() instanceof SagaEventListener)
                {
                    /** @var SagaEventListener $sagaEventListenerAnnotation */
                    $sagaEventListenerAnnotation = $annotationData->getAnnotation();

                    SagaConfigurationGuard::guardFirstEventListenerArgumentIsEvent(
                        $sagaNamespace,
                        $annotationData->getArguments()
                    );

                    $this->sagasListeners[$sagaNamespace][] = SagaListenerConfiguration::create(
                        $sagaNamespace,
                        $annotationData->getArguments()[0]->getClass()->getName(),
                        (string) $sagaEventListenerAnnotation->getContainingIdentifierProperty()
                    );
                }
            }
        }

        return $this->sagasListeners[$sagaNamespace];
    }
}
