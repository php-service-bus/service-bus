<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Infrastructure\Alerting;

use Amp\Promise;
use Amp\Success;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\Environment;
use ServiceBus\TelegramBot\Api\Method\Message\SendMessage;
use ServiceBus\TelegramBot\Api\Type\Chat\ChatId;
use ServiceBus\TelegramBot\Interaction\InteractionsProvider;
use ServiceBus\TelegramBot\Interaction\Result\Fail;
use ServiceBus\TelegramBot\TelegramCredentials;
use function Amp\call;
use function ServiceBus\Common\throwableDetails;
use function ServiceBus\Common\throwableMessage;

/**
 * The notification will be sent to the Telegram channel.
 * Sending messages is disabled for all environments except production.
 */
final class TelegramAlertingProvider implements AlertingProvider
{
    /**
     * @var InteractionsProvider
     */
    private $interactionsProvider;

    /**
     * @var TelegramCredentials
     */
    private $credentials;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $defaultChatId;

    /**
     * @psalm-param non-empty-string $defaultChatId
     */
    public function __construct(
        InteractionsProvider $interactionsProvider,
        TelegramCredentials $credentials,
        Environment $environment,
        string $defaultChatId,
        ?LoggerInterface $logger = null
    ) {
        $this->interactionsProvider = $interactionsProvider;
        $this->credentials          = $credentials;
        $this->environment          = $environment;
        $this->defaultChatId        = $defaultChatId;
        $this->logger               = $logger ?? new NullLogger();
    }

    public function send(AlertMessage $message, ?AlertContext $context = null): Promise
    {
        if ($this->environment->isDebug())
        {
            return new Success();
        }

        $context = $context ?? new AlertContext();

        return call(
            function () use ($message, $context): \Generator
            {
                try
                {
                    $toChat = (string) $context->toTopic !== ''
                        ? (string) $context->toTopic
                        : $this->defaultChatId;

                    $method = SendMessage::create(
                        chatId: new ChatId($toChat),
                        text: $message->content
                    )->useMarkdown();

                    if ($context->toDrawAttention === false)
                    {
                        $method->disableNotification();
                    }

                    /** @var \ServiceBus\TelegramBot\Interaction\Result\Result */
                    $result = yield $this->interactionsProvider->call(
                        method: $method,
                        credentials: $this->credentials
                    );

                    if ($result instanceof Fail)
                    {
                        throw new \RuntimeException(
                            \sprintf('Delivery to Telegram failed: %s', $result->errorMessage)
                        );
                    }
                }
                catch (\Throwable $throwable)
                {
                    $this->logger->error(throwableMessage($throwable), throwableDetails($throwable));
                }
            }
        );
    }
}
