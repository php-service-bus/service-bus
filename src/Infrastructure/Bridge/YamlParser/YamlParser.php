<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\Bridge\YamlParser;

use Desperado\ConcurrencyFramework\Infrastructure\Bridge\YamlParser\Exceptions\ParseYamlException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * YAML parser
 */
class YamlParser
{
    /**
     * Parse YAML file content
     *
     * @param string $yamlContent
     *
     * @return array
     *
     * @throws ParseYamlException
     */
    public static function parse(string $yamlContent): array
    {
        try
        {
            $parameters = (new Parser())->parse($yamlContent);

            if(true === \is_array($parameters) && 0 !== \count($parameters))
            {
                return $parameters;
            }
        }
        catch(ParseException $parseException)
        {
            throw new ParseYamlException(
                $parseException->getMessage(), $parseException->getCode(), $parseException
            );
        }

        return [];
    }

    /**
     * Close constructor
     *
     * @codeCoverageIgnore
     */
    final private function __construct()
    {

    }
}
