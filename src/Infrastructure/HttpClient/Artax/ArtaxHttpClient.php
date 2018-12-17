<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Infrastructure\HttpClient\Artax;

use Amp\Artax\Client;
use Amp\Artax\Cookie\ArrayCookieJar;
use Amp\Artax\DefaultClient;
use Amp\Artax\Request;
use Amp\Artax\Response;
use function Amp\ByteStream\pipe;
use function Amp\call;
use function Amp\File\open;
use Amp\File\StatCache;
use Amp\Promise;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Infrastructure\HttpClient\Data\HttpRequest;
use Desperado\ServiceBus\Infrastructure\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Log\NullLogger;

/**
 * Artax (amphp-based) http client
 *
 * @codeCoverageIgnore
 */
final class ArtaxHttpClient implements HttpClient
{
    /**
     * Artax http client
     *
     * @var Client
     */
    private $handler;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Client|null          $handler
     * @param int                  $transferTimeout Transfer timeout in milliseconds until an HTTP request is
     *                                              automatically aborted, use 0 to disable
     * @param LoggerInterface|null $logger
     */
    public function __construct(Client $handler = null, int $transferTimeout = 5000, LoggerInterface $logger = null)
    {
        $this->handler = $handler ?? new DefaultClient(new ArrayCookieJar());
        $this->logger  = $logger ?? new NullLogger();

        if(true === \method_exists($this->handler, 'setOption'))
        {
            $this->handler->setOption(Client::OP_TRANSFER_TIMEOUT, $transferTimeout);
        }
    }

    /**
     * @inheritdoc
     */
    public function execute(HttpRequest $requestData): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(HttpRequest $requestData): \Generator
            {
                $generator = 'GET' === $requestData->httpMethod()
                    ? $this->executeGet($requestData)
                    : $this->executePost($requestData);

                return yield from $generator;
            },
            $requestData
        );
    }

    /**
     * @inheritdoc
     */
    public function download(string $filePath, string $destinationDirectory, string $fileName): Promise
    {
        $client = $this->handler;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(string $filePath, string $destinationDirectory, string $fileName) use ($client): \Generator
            {
                /** @var Response $response */
                $response         = yield $client->request(new Request($filePath));
                $tmpDirectoryPath = \tempnam(\sys_get_temp_dir(), 'artax-streaming-');

                /** @var \Amp\File\Handle $tmpFile */
                $tmpFile = yield open($tmpDirectoryPath, 'w');

                yield pipe($response->getBody(), $tmpFile);

                $destinationFilePath = \sprintf(
                    '%s/%s',
                    \rtrim($destinationDirectory, '/'),
                    \ltrim($fileName, '/')
                );

                yield $tmpFile->close();
                yield rename($tmpDirectoryPath, $destinationFilePath);

                StatCache::clear($tmpDirectoryPath);

                return $destinationFilePath;
            },
            $filePath, $destinationDirectory, $fileName
        );
    }

    /**
     * Handle GET query
     *
     * @param HttpRequest $requestData
     *
     * @return \Generator<\GuzzleHttp\Psr7\Response>
     *
     * @throws \Throwable
     */
    private function executeGet(HttpRequest $requestData): \Generator
    {
        $request = (new Request($requestData->url(), $requestData->httpMethod()))
            ->withHeaders($requestData->headers());

        return self::doRequest(
            $this->handler,
            $request,
            $this->logger
        );
    }

    /**
     * Execute POST request
     *
     * @param HttpRequest $requestData
     *
     * @return \Generator<\GuzzleHttp\Psr7\Response>
     *
     * @throws \Throwable
     */
    private function executePost(HttpRequest $requestData): \Generator
    {
        /** @var ArtaxFormBody|string|null $body */
        $body = $requestData->body();

        $request = (new Request($requestData->url(), $requestData->httpMethod()))
            ->withBody(
                $body instanceof ArtaxFormBody
                    ? $body->preparedBody()
                    : $body
            )
            ->withHeaders($requestData->headers());

        return self::doRequest($this->handler, $request, $this->logger);
    }

    /**
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param Client          $client
     * @param Request         $request
     * @param LoggerInterface $logger
     *
     * @return \Generator<\GuzzleHttp\Psr7\Response>
     *
     * @throws \Throwable
     */
    private static function doRequest(Client $client, Request $request, LoggerInterface $logger): \Generator
    {
        $requestId = uuid();

        try
        {
            self::logRequest($logger, $request, $requestId);

            /** @var Response $artaxResponse */
            $artaxResponse = yield $client->request($request);

            /** @var Psr7Response $response */
            $response = yield from self::adaptResponse($artaxResponse);

            unset($artaxResponse);

            self::logResponse($logger, $response, $requestId);

            return $response;
        }
        catch(\Throwable $throwable)
        {
            self::logThrowable($logger, $throwable, $requestId);

            throw $throwable;
        }
    }

    /**
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param Response $response
     *
     * @return \Generator<\GuzzleHttp\Psr7\Response>
     */
    private static function adaptResponse(Response $response): \Generator
    {
        /** @psalm-suppress InvalidCast Invalid read stream handle */
        $responseBody = (string) yield $response->getBody();

        return new Psr7Response(
            $response->getStatus(),
            $response->getHeaders(),
            $responseBody,
            $response->getProtocolVersion(),
            $response->getReason()
        );
    }

    /**
     * @param LoggerInterface $logger
     * @param Request         $request
     * @param string          $requestId
     *
     * @return void
     */
    private static function logRequest(LoggerInterface $logger, Request $request, string $requestId): void
    {
        $logger->debug(
            'Request: [{requestMethod}] {requestUri} {requestHeaders}', [
                'requestMethod'  => $request->getMethod(),
                'requestUri'     => $request->getUri(),
                'requestHeaders' => $request->getHeaders(),
                'requestId'      => $requestId
            ]
        );
    }

    /**
     * @param LoggerInterface $logger
     * @param Psr7Response    $response
     * @param string          $requestId
     *
     * @return void
     */
    private static function logResponse(LoggerInterface $logger, Psr7Response $response, string $requestId): void
    {
        $logger->debug(
            'Response: {responseHttpCode} {responseContent} {responseHeaders}', [
                'responseHttpCode' => $response->getStatusCode(),
                'responseContent'  => (string) $response->getBody(),
                'responseHeaders'  => $response->getHeaders(),
                'requestId'        => $requestId
            ]
        );
    }

    /**
     * @param LoggerInterface $logger
     * @param \Throwable      $throwable
     * @param string          $requestId
     *
     * @return void
     */
    private static function logThrowable(LoggerInterface $logger, \Throwable $throwable, string $requestId): void
    {
        $logger->error(
            'During the execution of the request with identifier "{requestId}" an exception was caught: "{throwableMessage}"',
            [
                'requestId'        => $requestId,
                'throwableMessage' => $throwable->getMessage(),
                'throwablePoint'   => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())
            ]
        );
    }
}
