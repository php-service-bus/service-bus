<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Annotations\Services;

use Desperado\Domain\Annotations\AbstractAnnotation;
use Desperado\ServiceBus\Annotations\Services\Traits;

/**
 * Annotation indicating to the query handler
 *
 * Handler can be called via the http request (and not only from the transport bus, for example, a rabbitMq. The
 * query can still be called using the transport bus).
 *
 * The query should implement the interface "\Desperado\ServiceBus\Messages\HttpMessageInterface"
 *
 * To support working with the http entry point, you must specify the `$route` and `$method`
 *
 * @see HttpSupportTrait
 *
 * @Annotation
 * @Target("METHOD")
 */
class QueryHandler extends AbstractAnnotation
    implements MessageHandlerAnnotationInterface, HttpHandlerAnnotationInterface
{
    use Traits\LoggerChannelTrait;
    use Traits\HttpSupportTrait;
}
