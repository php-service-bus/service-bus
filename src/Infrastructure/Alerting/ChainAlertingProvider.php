<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Infrastructure\Alerting;

use Amp\Promise;
use function Amp\call;

/**
 *
 */
final class ChainAlertingProvider implements AlertingProvider
{
    /**
     * @var AlertingProvider[]
     */
    private $providers;

    /**
     * @psalm-param list<AlertingProvider> $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    public function send(AlertMessage $message, ?AlertContext $context = null): Promise
    {
        return call(
            function () use ($message, $context): \Generator
            {
                $context = $context ?? new AlertContext();

                foreach ($this->providers as $alertingProvider)
                {
                    try
                    {
                        yield $alertingProvider->send($message, $context);
                    }
                    catch (\Throwable)
                    {
                        /** Not interests */
                    }
                }
            }
        );
    }
}
