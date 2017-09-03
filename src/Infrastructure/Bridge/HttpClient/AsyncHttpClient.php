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

namespace Desperado\Framework\Infrastructure\Bridge\HttpClient;

use Desperado\Framework\Domain\ParameterBag;
use EventLoop\EventLoop;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use WyriHaximus\React\GuzzlePsr7\HttpClientAdapter;

/**
 * Async http client
 */
class AsyncHttpClient implements HttpClientInterface
{
    private const DEFAULT_REQUEST_OPTIONS = [
        'connect_timeout' => 10,
        'timeout'         => 10,
        'exceptions'      => false,
        'allow_redirects' => true
    ];

    /**
     * Guzzle http handler stack
     *
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * Http client
     *
     * @var Client
     */
    private $httpClient;

    /**
     * @param callable|null $requestHandler
     * @param array         $options
     */
    public function __construct(callable $requestHandler = null, array $options = [])
    {
        $this->handlerStack = HandlerStack::create(
            $requestHandler ?? new HttpClientAdapter(EventLoop::getLoop())
        );

        $options = \array_merge(
            self::DEFAULT_REQUEST_OPTIONS,
            $options,
            ['handler' => $this->handlerStack]
        );

        $this->httpClient = new Client($options);
    }

    /**
     * @inheritdoc
     */
    public function pushMiddleware(callable $middleware): void
    {
        $this->handlerStack->push($middleware);
    }

    /**
     * @inheritdoc
     */
    public function useLogger(LoggerInterface $logger, $logHeaders = false): void
    {
        $this->handlerStack->push(Middleware::log($logger, new MessageFormatter()));
    }

    /**
     * @inheritdoc
     */
    public function post(HttpRequest $request, callable $resultHandler): void
    {
        $promise = $this->httpClient->postAsync(
            $request->getUrl(),
            self::getRequestParameters('POST', $request->getParameters(), $request->getHeaders())

        );

        self::processPromise($promise, $resultHandler);
    }

    /**
     * @inheritdoc
     */
    public function put(HttpRequest $request, callable $resultHandler): void
    {
        $promise = $this->httpClient->putAsync(
            $request->getUrl(),
            self::getRequestParameters('PUT', $request->getParameters(), $request->getHeaders())

        );

        self::processPromise($promise, $resultHandler);
    }

    /**
     * @inheritdoc
     */
    public function get(HttpRequest $request, callable $resultHandler): void
    {
        $promise = $this->httpClient->getAsync(
            $request->getUrl(),
            self::getRequestParameters('GET', $request->getParameters(), $request->getHeaders())

        );

        self::processPromise($promise, $resultHandler);
    }

    /**
     * Process promise
     *
     * @param PromiseInterface $promise
     * @param callable         $resultHandler
     *
     * @return void
     */
    private static function processPromise(PromiseInterface $promise, callable $resultHandler): void
    {
        $promise->then(
            function(Response $response) use ($resultHandler)
            {
                return $resultHandler(null, $response);
            },
            function(\Throwable $throwable) use ($resultHandler)
            {
                return $resultHandler($throwable, null);
            }
        );
    }

    /**
     * Get request parameters
     *
     * @param string       $httpMethod
     * @param mixed        $payload
     * @param ParameterBag $headers
     *
     * @return array
     */
    private static function getRequestParameters(string $httpMethod, $payload, ParameterBag $headers): array
    {
        if('GET' === $httpMethod)
        {
            $payloadIndex = 'query';
        }
        else
        {
            $payloadIndex = true === \is_array($payload) ? 'form_params' : 'body';
        }

        return [
            $payloadIndex => $payload,
            'headers'     => $headers->all()
        ];
    }
}
