<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Metrics;

use Desperado\Domain\ParameterBag;
use Desperado\Infrastructure\Bridge\HttpClient\AsyncGuzzleHttpClient;
use Desperado\Infrastructure\Bridge\HttpClient\HttpRequest;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * Influx Data collector
 */
class InfluxDbCollector implements MetricsCollectorInterface
{
    private const DEFAULT_BULK_SIZE = 20;

    /**
     * Guzzle http client
     *
     * @var AsyncGuzzleHttpClient
     */
    private $httpClient;

    /**
     * Endpoint URL
     *
     * @var string
     */
    private $endpointUrl;

    /**
     * Request headers bag
     *
     * @var ParameterBag
     */
    private $requestHeaders;

    /**
     * Number of entries required to send to the database
     *
     * @var int
     */
    private $bulkSize;

    /**
     * Local storage buffer
     *
     * @var array
     */
    private $entriesBuffer = [];

    /**
     * @param string $connectionDSN
     * @param int    $bulkSize
     * @param string $nameServer
     */
    public function __construct(
        string $connectionDSN,
        int $bulkSize = self::DEFAULT_BULK_SIZE,
        string $nameServer = '8.8.8.8'
    )
    {
        $this->bulkSize = $bulkSize;
        $this->httpClient = new AsyncGuzzleHttpClient(null, $nameServer);

        $dsnParameters = new ParameterBag(\parse_url($connectionDSN));

        $this->endpointUrl = \sprintf(
            'http://%s:%d/write?db=%s&precision=ms',
            $dsnParameters->getAsString('host', 'localhost'),
            $dsnParameters->getAsInt('port', 8086),
            \ltrim($dsnParameters->getAsString('path', 'path'), '/')
        );

        $this->requestHeaders = new ParameterBag([
            $dsnParameters->getAsString('user'),
            $dsnParameters->getAsString('pass')
        ]);
    }

    /**
     * @inheritdoc
     */
    public function push(string $type, $value, array $tags = []): PromiseInterface
    {
        return new Promise(
            function($resolve, $reject) use ($type, $value, $tags)
            {
                $this->entriesBuffer[] = $this->createBodyContent($type, $value, $tags);

                try
                {
                    try
                    {
                        $requestBody = \implode(\PHP_EOL, $this->entriesBuffer);

                        $httpRequestData = new HttpRequest(
                            $this->endpointUrl, $requestBody, $this->requestHeaders
                        );

                        if($this->bulkSize <= \count($this->entriesBuffer))
                        {
                            $this->entriesBuffer = [];

                            $this->httpClient
                                ->post($httpRequestData)
                                ->then(
                                    function() use ($resolve)
                                    {
                                        return $resolve();
                                    },
                                    function(\Throwable $throwable) use ($reject)
                                    {
                                        return $reject($throwable);
                                    }
                                );
                        }

                    }
                    catch(\Throwable $throwable)
                    {
                        return $reject($throwable);
                    }

                    return $resolve();
                }
                catch(\Throwable $throwable)
                {
                    return $reject($throwable);
                }
            }
        );
    }


    /**
     * Create request body content
     *
     * @param string $type
     * @param mixed  $value
     * @param array  $tags
     *
     * @return string
     */
    private function createBodyContent(string $type, $value, array $tags = []): string
    {
        $bodyString = $type;

        if(0 !== \count($tags))
        {
            $bodyString .= \sprintf(
                ',%s',
                self::arrayToString(
                    self::escapeCharacters(
                        self::formatTags($tags),
                        true
                    )
                )
            );
        }

        $bodyString .= \sprintf(
            ' %s,value=%s',
            self::arrayToString(
                self::escapeCharacters(
                    $this->getProcessInfo(), false
                )
            ),
            $value
        );

        return $bodyString;
    }

    /**
     * Get current process info
     *
     * @return array
     */
    private function getProcessInfo(): array
    {
        static $processInfo;

        if(null === $processInfo)
        {
            $processInfo = self::formatTags([
                    'php.gid'           => \getmygid(),
                    'php.uid'           => \getmyuid(),
                    'php.pid'           => \getmypid(),
                    'php.inode'         => \getmyinode(),
                    'instance.hostname' => \gethostname()
                ]
            );
        }

        return $processInfo;
    }

    /**
     * Array key/value to string
     *
     * @param array $array
     *
     * @return string
     */
    private static function arrayToString(array $array): string
    {
        $strParts = [];

        foreach($array as $key => $value)
        {
            $strParts[] = \sprintf('%s=%s', $key, $value);
        }

        return \implode(',', $strParts);
    }

    /**
     * Escape key/value string
     *
     * @param array $array
     * @param bool  $escapeValues
     *
     * @return array
     */
    private static function escapeCharacters(array $array, bool $escapeValues)
    {
        $result = [];

        foreach($array as $key => $value)
        {
            $result[self::addSlashes($key)] = true === $escapeValues ? self::addSlashes($value) : $value;
        }

        return $result;
    }

    /**
     * Add slashes for space, comma, etc.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private static function addSlashes($value)
    {
        return \str_replace([' ', ',', '='], ['\ ', '\,', '\='], $value);
    }

    /**
     * Format tags representation
     *
     * @param array $tags
     *
     * @return array
     */
    private static function formatTags(array $tags): array
    {
        $result = [];

        foreach($tags as $key => $value)
        {
            if(true === \is_bool($value))
            {
                $result[$key] = true === $value ? 'true' : 'false';
            }
            else if(true === \is_null($value))
            {
                $result[$key] = 'null';
            }
            else if(true === \is_int($value))
            {
                $result[$key] = \sprintf('%di', $value);
            }
            else if('' === (string) $value)
            {
                $result[$key] = '""';
            }
            else
            {
                $result[$key] = self::escapeFieldValue($value);
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    private static function escapeFieldValue($value): string
    {
        $escapedValue = str_replace('"', '\"', $value);

        return \sprintf('"%s"', $escapedValue);
    }
}
