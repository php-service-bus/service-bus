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

use Psr\Log\LoggerInterface;

/**
 * Http client
 */
interface HttpClientInterface
{
    /**
     * Apply middleware
     *
     * @param callable $middleware
     *
     * @return void
     */
    public function pushMiddleware(callable $middleware): void;

    /**
     * Apply logger
     *
     * @param LoggerInterface $logger
     * @param bool            $logHeaders
     *
     * @return void
     */
    public function useLogger(LoggerInterface $logger, $logHeaders = false): void;

    /**
     * Execute POST query
     *
     * Handler callable example:
     *
     * function (\Throwable $error = null, \GuzzleHttp\Psr7\Response $response = null) {
     *
     * }
     *
     * @param HttpRequest $request
     * @param callable    $resultHandler
     *
     * @return void
     */
    public function post(HttpRequest $request, callable $resultHandler): void;

    /**
     * Execute PUT query
     *
     * Handler callable example:
     *
     * function (\Throwable $error = null, \GuzzleHttp\Psr7\Response $response = null) {
     *
     * }
     *
     * @param HttpRequest $request
     * @param callable    $resultHandler
     *
     * @return void
     */
    public function put(HttpRequest $request, callable $resultHandler): void;

    /**
     * Execute GET query
     *
     * Handler callable example:
     *
     * function (\Throwable $error = null, \GuzzleHttp\Psr7\Response $response = null) {
     *
     * }
     *
     * @param HttpRequest $request
     * @param callable    $resultHandler
     *
     * @return void
     */
    public function get(HttpRequest $request, callable $resultHandler): void;
}
