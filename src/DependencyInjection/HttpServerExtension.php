<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\DependencyInjection;

use Desperado\ServiceBus\DependencyInjection\Traits\LoadServicesTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection;

/**
 * Share http server extensions
 */
class HttpServerExtension extends DependencyInjection\Extension\Extension
{
    use LoadServicesTrait;

    /**
     * @inheritDoc
     */
    public function load(array $configs, DependencyInjection\ContainerBuilder $container)
    {
        $loader = new DependencyInjection\Loader\XmlFileLoader($container, new FileLocator());
        $loader->load(__DIR__ . '/../Resources/config/extensions/react_http_server.xml');
    }
}