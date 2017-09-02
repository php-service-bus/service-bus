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

namespace Desperado\ConcurrencyFramework\Common\Serializer;

use Desperado\ConcurrencyFramework\Common\Serializer\Exceptions\JsonSerializationException;

/**
 * Json serializer
 */
class JsonSerializeHandler
{
    /**
     * Execute serialize
     *
     * @param mixed $content
     *
     * @return string
     *
     * @throws JsonSerializationException
     */
    public static function serialize($content): string
    {
        \json_last_error();

        $json = \json_encode($content);

        if(\JSON_ERROR_NONE === \json_last_error())
        {
            return $json;
        }

        throw new JsonSerializationException(\json_last_error_msg());
    }

    /**
     * Execute unserialize string
     *
     * @param string $content
     *
     * @return mixed
     *
     * @throws JsonSerializationException
     */
    public static function unserialize(string $content)
    {
        \json_last_error();

        $data = \json_decode($content, true);

        if(\JSON_ERROR_NONE === \json_last_error())
        {
            return $data;
        }

        throw new JsonSerializationException(\json_last_error_msg());
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
