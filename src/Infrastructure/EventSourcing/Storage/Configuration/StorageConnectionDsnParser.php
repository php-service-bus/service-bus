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

namespace Desperado\Framework\Infrastructure\EventSourcing\Storage\Configuration;

use Desperado\Framework\Domain\ParameterBag;

/**
 * Connection DSN parser
 */
class StorageConnectionDsnParser
{
    /**
     * Parse DSN string
     *
     * @param string $connectionDSN
     *
     * @return ParameterBag
     */
    public static function parse(string $connectionDSN): ParameterBag
    {
        $queryParts = [];
        $parsedUrl = new ParameterBag(\parse_url($connectionDSN));

        \parse_str($parsedUrl->getAsString('query'), $queryParts);

        $parametersBag = new ParameterBag($queryParts);

        $pathParts = \explode(':', $parsedUrl->getAsString('path'));

        $parametersBag->set('host', !empty($pathParts[0]) ? $pathParts[0] : 'localhost');
        $parametersBag->set('port', !empty($pathParts[1]) ? $pathParts[1] : 5342);
        $parametersBag->set('driver', $parsedUrl->getAsString('schema', 'inMemory'));

        return $parametersBag;
    }

    /**
     * Close constructor
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {

    }
}
