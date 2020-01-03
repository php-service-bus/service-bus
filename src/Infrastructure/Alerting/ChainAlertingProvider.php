<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Infrastructure\Alerting;

use Amp\Promise;
use function Amp\call;

/**
 *
 */
final class ChainAlertingProvider implements AlertingProvider
{
    /** @var AlertingProvider[] */
    private $providers;

    /**
     * @param AlertingProvider[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @inheritDoc
     */
    public function send(AlertMessage $message, ?AlertContext $context = null): Promise
    {
        return call(
            function() use ($message, $context): \Generator
            {
                $context = $context ?? new AlertContext();

                foreach($this->providers as $alertingProvider)
                {
                    try
                    {
                        yield $alertingProvider->send($message, $context);
                    }
                    catch(\Throwable $throwable)
                    {
                        /** Not interests */
                    }
                }
            }
        );
    }
}
