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
use Desperado\MessageSerializer\Bridge\SymfonySerializerBridge;
use Desperado\MessageSerializer\MessageSerializer;
use Desperado\ServiceBus\Demo\Customer\Command\RegisterCustomerCommand;

include_once __DIR__ . '/../../vendor/autoload.php';

(new Dotenv())->load(__DIR__ . '/../.env');

$dsnParts = \parse_url(\getenv('TRANSPORT_CONNECTION_DSN'));

$command = RegisterCustomerCommand::create([
    'requestId'   => Uuid::new(),
    'userName'    => 'someCustomerUserName',
    'displayName' => 'someCustomerDisplayName',
    'email'       => 'desperado@minsk-info.ru',
    'password'    => 'qwerty'
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
