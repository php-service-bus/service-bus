<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Infrastructure\Alerting;

use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use ServiceBus\Environment;
use ServiceBus\Infrastructure\Alerting\AlertContext;
use ServiceBus\Infrastructure\Alerting\AlertMessage;
use ServiceBus\Infrastructure\Alerting\TelegramAlertingProvider;
use ServiceBus\TelegramBot\Interaction\InteractionsProvider;
use ServiceBus\TelegramBot\TelegramCredentials;
use function Amp\Promise\wait;
use function ServiceBus\Tests\filterLogMessages;

/**
 *
 */
final class TelegramAlertingProviderTest extends TestCase
{
    /** @test */
    public function inTestEnvironment(): void
    {
        $httpClient       = new TestAlertingHttpClient(new Response());
        $alertingProvider = new TelegramAlertingProvider(
            new InteractionsProvider($httpClient),
            new TelegramCredentials('927366182:AAHsjMy7u13tvCTgzSP-fPlIR89lhWgCOvn'),
            Environment::dev(),
            '100'
        );

        wait($alertingProvider->send(new AlertMessage('qwerty')));
    }

    /** @test */
    public function successDelivery(): void
    {
        $httpClient       = new TestAlertingHttpClient(new Response());
        $alertingProvider = new TelegramAlertingProvider(
            new InteractionsProvider($httpClient),
            new TelegramCredentials('927366182:AAHsjMy7u13tvCTgzSP-fPlIR89lhWgCOvn'),
            Environment::prod(),
            '100'
        );

        wait($alertingProvider->send(new AlertMessage('qwerty'), new AlertContext(false)));

        static::assertNotNull($httpClient->requestData);
    }

    /** @test */
    public function failedDelivery(): void
    {
        $logHandler = new TestHandler();
        $logger     = new Logger('tests', [$logHandler]);

        $httpClient       = new TestAlertingHttpClient(new Response(400));
        $alertingProvider = new TelegramAlertingProvider(
            new InteractionsProvider($httpClient),
            new TelegramCredentials('927366182:AAHsjMy7u13tvCTgzSP-fPlIR89lhWgCOvn'),
            Environment::prod(),
            '100',
            $logger
        );

        wait($alertingProvider->send(new AlertMessage('qwerty'), new AlertContext(false)));

        static::assertNotNull($httpClient->requestData);

        static::assertContains(
            'Delivery to Telegram failed: Incorrect server response code: 400',
            filterLogMessages($logHandler)
        );
    }
}
