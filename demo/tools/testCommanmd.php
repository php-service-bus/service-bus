<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

use Bunny\Client;
use Symfony\Component\Dotenv\Dotenv;
use Desperado\Domain\Uuid;
use Desperado\ServiceBus\Serializer\Bridge\SymfonySerializerBridge;
use Desperado\ServiceBus\Serializer\MessageSerializer;
use Desperado\ServiceBus\Demo\Command\TestCommand;

include_once __DIR__ . '/../../vendor/autoload.php';

(new Dotenv())->load(__DIR__ . '/../../.env');

$dsnParts = \parse_url(\getenv('TRANSPORT_CONNECTION_DSN'));

$command = TestCommand::create([
    'requestId' => Uuid::new()
]);

$client = (new Client($dsnParts))->connect();
$messageBody = (new MessageSerializer(new SymfonySerializerBridge()))->serialize($command);

$channel = $client->channel();
$result = $channel->publish(
    $messageBody,
    [],
    'testing',
    'demo'
);

echo true === $result ? 'true' : 'false';
echo \PHP_EOL;

$channel->close();
$client->disconnect();
