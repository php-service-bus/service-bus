<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Annotations;


/**
 * Base class of annotations
 */
abstract class AbstractAnnotation
{
    /**
     * @param array $data
     *
     * @throws UnknownAnnotationPropertyException
     */
    final public function __construct(array $data)
    {
        foreach($data as $key => $value)
        {
            $closure = function(string $key, $value)
            {
                if(false === \property_exists($this, $key))
                {
                    throw new UnknownAnnotationPropertyException($key, $this);
                }

                $this->{$key} = $value;
            };

            $closure->call($this, $key, $value);
        }
    }
}
