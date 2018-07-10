<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus\Configuration;

use Desperado\ServiceBus\MessageBus\Configuration\Annotations\CommandHandler;
use Desperado\ServiceBus\MessageBus\Configuration\Annotations\EventListener;
use Desperado\ServiceBus\MessageBus\Configuration\Annotations\ServiceBusAnnotationMarker;
use Doctrine\Common\Annotations as DoctrineAnnotations;

/**
 * Annotation-based configuration
 */
final class AnnotationBasedConfigurationLoader implements ConfigurationLoader
{
    /**
     * Annotations reader
     *
     * @var DoctrineAnnotations\Reader
     */
    private $reader;

    /**
     * @throws \RuntimeException
     */
    public function __construct()
    {
        $this->initReader();
    }

    /**
     * @inheritdoc
     */
    public function extractHandlers(object $service): MessageHandlerCollection
    {
        $collection = new MessageHandlerCollection();

        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $reflectionClass = new \ReflectionClass($service);

        foreach($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod)
        {
            /** @var ServiceBusAnnotationMarker[] $annotations */
            $annotations = $this->filterAnnotations(
                $this->extractMethodLevelAnnotations($reflectionMethod)
            );

            foreach($annotations as $annotation)
            {
                $factoryMethod = $annotation instanceof CommandHandler ? 'commandHandler' : 'eventListener';

                $collection->push(
                    MessageHandler::{$factoryMethod}(
                        self::createOptions($annotation),
                        $reflectionMethod
                    )
                );
            }
        }

        return $collection;
    }

    /**
     * Create options
     *
     * @param ServiceBusAnnotationMarker $annotation
     *
     * @return MessageHandlerOptions
     */
    private static function createOptions(ServiceBusAnnotationMarker $annotation): MessageHandlerOptions
    {
        /** @var CommandHandler|EventListener $annotation */

        $options = new MessageHandlerOptions();

        if(true === $annotation->validationEnabled())
        {
            $options->enableValidation($annotation->validationGroups());
        }

        return $options;
    }

    /**
     * Receive only supported annotations
     *
     * @param array $annotations
     *
     * @return array<mixed, \Desperado\ServiceBus\MessageBus\Configuration\Annotations\ServiceBusAnnotationMarker>
     */
    private function filterAnnotations(array $annotations): array
    {
        return \array_filter(
            \array_map(
                static function(object $annotation): ?ServiceBusAnnotationMarker
                {
                    return $annotation instanceof ServiceBusAnnotationMarker ? $annotation : null;
                },
                $annotations
            )
        );
    }

    /**
     * Extract all method-level annotations
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     *
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return array<mixed, object>
     */
    private function extractMethodLevelAnnotations(\ReflectionMethod $reflectionMethod): array
    {
        return $this->reader->getMethodAnnotations($reflectionMethod);
    }

    /**
     * @return void
     *
     * @throws \RuntimeException
     */
    private function initReader(): void
    {
        /**
         * @noinspection   PhpDeprecationInspection
         * @psalm-suppress DeprecatedMethod
         */
        DoctrineAnnotations\AnnotationRegistry::registerLoader('class_exists');

        try
        {
            $this->reader = new DoctrineAnnotations\AnnotationReader();
        }
        catch(\Throwable $throwable)
        {
            throw new \RuntimeException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }
}
