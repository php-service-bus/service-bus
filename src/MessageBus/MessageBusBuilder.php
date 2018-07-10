<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus;

use Desperado\Contracts\Common\Message;
use Desperado\ServiceBus\MessageBus\Configuration\ConfigurationLoader;
use Desperado\ServiceBus\MessageBus\Configuration\MessageHandler;
use Desperado\ServiceBus\MessageBus\Exceptions\NoMessageArgumentFound;
use Desperado\ServiceBus\MessageBus\Exceptions\NonUniqueCommandHandler;
use Desperado\ServiceBus\MessageBus\Task\Arguments\ArgumentResolver;
use Desperado\ServiceBus\MessageBus\Task\TaskProcessor;
use Desperado\ServiceBus\MessageBus\Task\TaskMap;
use Desperado\ServiceBus\MessageBus\Task\ValidateMessageTask;
use Psr\Log\LoggerInterface;

/**
 * Compile message bus
 */
final class MessageBusBuilder
{
    /**
     * @var ConfigurationLoader
     */
    private $configurationLoader;

    /**
     * Tasks list
     *
     * @var TaskMap
     */
    private $taskMap;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ConfigurationLoader $configurationLoader
     * @param LoggerInterface     $logger
     */
    public function __construct(ConfigurationLoader $configurationLoader, LoggerInterface $logger)
    {
        $this->configurationLoader = $configurationLoader;
        $this->logger              = $logger;

        $this->taskMap = new TaskMap();
    }

    /**
     * Register service handlers
     *
     * @param object           $service
     * @param ArgumentResolver ...$argumentResolvers
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\MessageBus\Exceptions\NoMessageArgumentFound
     * @throws \Desperado\ServiceBus\MessageBus\Exceptions\NonUniqueCommandHandler
     */
    public function configureService(object $service, ArgumentResolver ... $argumentResolvers): void
    {
        $handlers = $this->configurationLoader->extractHandlers($service);

        foreach($handlers as $handler)
        {
            $this->registerHandler($handler, $service, ...$argumentResolvers);
        }
    }

    /**
     * Register handler
     *
     * @param MessageHandler   $handler
     * @param object           $service
     * @param ArgumentResolver ...$argumentResolvers
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\MessageBus\Exceptions\NoMessageArgumentFound
     * @throws \Desperado\ServiceBus\MessageBus\Exceptions\NonUniqueCommandHandler
     */
    public function registerHandler(
        MessageHandler $handler,
        object $service,
        ArgumentResolver ... $argumentResolvers
    ): void
    {
        $this->assertMessageClassSpecifiedInArguments($service, $handler);
        $this->assertUniqueCommandHandler($handler);

        /** @var string $messageClass */
        $messageClass = $handler->messageClass();

        $messageTask = new TaskProcessor($handler->toClosure($service), $handler->arguments(), $argumentResolvers);

        if(true === $handler->options()->validationEnabled())
        {
            $messageTask = new ValidateMessageTask($messageTask, $handler->options()->validationGroups());
        }

        $this->taskMap->push(
            $messageClass,
            $messageTask
        );
    }

    /**
     * Compile message bus
     *
     * @return MessageBus
     */
    public function compile(): MessageBus
    {
        $this->logger->debug(
            'The message bus was successfully compiled. Total number of message handlers: "{messageHandlersCount}"', [
                'messageHandlersCount' => \count($this->taskMap)
            ]
        );

        return new MessageBus($this->taskMap);
    }

    /**
     * @param MessageHandler $handler
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\MessageBus\Exceptions\NonUniqueCommandHandler
     */
    private function assertUniqueCommandHandler(MessageHandler $handler): void
    {
        /** @var string $messageClass */
        $messageClass = $handler->messageClass();

        if(true === $handler->isCommandHandler() && true === $this->taskMap->hasTask($messageClass))
        {
            throw new NonUniqueCommandHandler(
                \sprintf(
                    'The handler for the "%s" command has already been added earlier. You can not add multiple command handlers',
                    $messageClass
                )
            );
        }
    }

    /**
     * @param object         $service
     * @param MessageHandler $handler
     *
     * @return void
     */
    private function assertMessageClassSpecifiedInArguments(object $service, MessageHandler $handler): void
    {
        if(null === $handler->messageClass() || '' === (string) $handler->messageClass())
        {
            throw new NoMessageArgumentFound(
                \sprintf(
                    'In the method of "%s:%s" is not found an argument of type "%s"',
                    \get_class($service),
                    $handler->methodName(),
                    Message::class
                )
            );
        }
    }
}
