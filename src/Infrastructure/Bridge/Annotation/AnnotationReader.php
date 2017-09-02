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

namespace Desperado\ConcurrencyFramework\Infrastructure\Bridge\Annotation;

use Desperado\ConcurrencyFramework\Domain\Annotation\AbstractAnnotation;
use Doctrine\Common\Annotations as DoctrineAnnotations;

/**
 * Annotation reader wrapper
 */
class AnnotationReader
{
    /**
     * Annotations reader
     *
     * @var DoctrineAnnotations\Reader
     */
    private $reader;

    /**
     * @param DoctrineAnnotations\Reader|null $reader
     */
    public function __construct(DoctrineAnnotations\Reader $reader = null)
    {
        $this->reader = $reader ?? new DoctrineAnnotations\AnnotationReader();

        self::initAnnotationsAutoLoader();
    }

    /**
     * Load class annotations
     *
     * @param object|string $class
     *
     * @return array
     */
    public function loadClassAnnotations($class): array
    {
        $className = true === \is_object($class) ? \get_class($class) : $class;

        return $this->reader->getClassAnnotations(new \ReflectionClass($className));
    }

    /**
     * Load class methods annotations
     *
     * [
     *    0 => [
     *        'annotation' => AbstractAnnotation instance,
     *        'method'     => methodName
     *    ],
     *    ...
     * ]
     *
     * @param object|string $class
     *
     * @return array
     */
    public function loadClassMethodsAnnotation($class): array
    {
        $className = true === \is_object($class) ? \get_class($class) : $class;

        $reflectionClass = new \ReflectionClass($className);
        $annotations = [];

        \array_map(
            function(\ReflectionMethod $method) use (&$annotations)
            {
                $list = $this->reader->getMethodAnnotations($method);

                if(0 !== \count($list))
                {
                    $annotations = \array_merge(
                        $annotations,
                        \array_map(
                            function(AbstractAnnotation $each) use ($method)
                            {
                                return [
                                    'annotation' => $each,
                                    'method'     => $method->getName(),
                                    'arguments'  => $method->getParameters()
                                ];
                            },
                            $list
                        )
                    );
                }
            },
            $reflectionClass->getMethods()
        );

        return $annotations;
    }

    /**
     * Init annotation registry
     *
     * @return void
     */
    private static function initAnnotationsAutoLoader(): void
    {
        foreach(\spl_autoload_functions() as $autoLoader)
        {
            if(isset($autoLoader[0]) && \is_object($autoLoader[0]))
            {
                DoctrineAnnotations\AnnotationRegistry::registerLoader([$autoLoader[0], 'loadClass']);
                break;
            }
        }
    }
}
