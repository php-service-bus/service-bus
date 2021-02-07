<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Infrastructure\Alerting;

use Amp\Promise;
use Amp\Success;
use GuzzleHttp\Psr7\Response;
use ServiceBus\HttpClient\HttpClient;
use ServiceBus\HttpClient\HttpRequest;
use ServiceBus\HttpClient\RequestContext;

/**
 *
 */
final class TestAlertingHttpClient implements HttpClient
{
    /** @var HttpRequest */
    public $requestData;

    /** @var Response */
    private $withResponse;

    public function __construct(Response $withResponse)
    {
        $this->withResponse = $withResponse;
    }

    /**
     * @inheritDoc
     */
    public function execute(HttpRequest $requestData, ?RequestContext $context = null): Promise
    {
        $this->requestData = $requestData;

        return new Success($this->withResponse);
    }

    /**
     * @inheritDoc
     */
    public function download(string $filePath, string $destinationDirectory, string $fileName, ?RequestContext $context = null): Promise
    {
        return new Success();
    }
}
