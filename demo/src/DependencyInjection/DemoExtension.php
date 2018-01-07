<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Demo\DependencyInjection;

use Desperado\ServiceBus\DependencyInjection\Traits\LoadServicesTrait;
use Symfony\Component\DependencyInjection;

/**
 *
 */
class DemoExtension extends DependencyInjection\Extension\Extension
{
    use LoadServicesTrait;

    /**
     * @inheritDoc
     */
    public function load(array $configs, DependencyInjection\ContainerBuilder $container)
    {
        self::loadFromDirectory(__DIR__ . '/../Resources/config', $container);
    }
}
