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

namespace Desperado\ServiceBus\Infrastructure\HttpClient;

use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\HttpClient\Data\HttpRequest;

/**
 * Http client interface
 */
interface HttpClient
{
    /**
     * Execute request
     *
     * @param HttpRequest $requestData
     *
     * @return Promise<\GuzzleHttp\Psr7\Response>
     *
     * @throws \Throwable
     */
    public function execute(HttpRequest $requestData): Promise;

    /**
     * Download file
     *
     * @param string $filePath
     * @param string $destinationDirectory
     * @param string $fileName
     *
     * @return Promise<string>
     */
    public function download(string $filePath, string $destinationDirectory, string $fileName): Promise;
}
