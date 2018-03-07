<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Task\Interceptors;

use Bunny\Channel;
use Desperado\Domain\ParameterBag;
use Desperado\MessageSerializer\Bridge\SymfonySerializerBridge;
use Desperado\MessageSerializer\MessageSerializer;
use Desperado\ServiceBus\Application\Context\ExecutionContextInterface;
use Desperado\ServiceBus\Services\Handlers\CommandExecutionParameters;
use Desperado\ServiceBus\Task\Interceptors\ValidateInterceptor;
use Desperado\ServiceBus\Task\Task;
use Desperado\ServiceBus\Tests\Services\Stabs\TestServiceCommand;
use Desperado\ServiceBus\Tests\Services\Stabs\TestServiceEvent;
use Desperado\ServiceBus\Tests\TestApplicationContext;
use Desperado\ServiceBus\Transport\Context\OutboundMessageContext;
use Desperado\ServiceBus\Transport\Message\Message;
use Desperado\ServiceBus\Transport\RabbitMqTransport\RabbitMqIncomingContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ValidatorBuilder;
use Symfony\Component\Validator;

/**
 *
 */
class ValidateInterceptorTest extends TestCase
{
    /**
     * @var Validator\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @var ExecutionContextInterface
     */
    private $context;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var Channel $channel */
        $channel = $this->getMockBuilder(Channel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $incomingContext = RabbitMqIncomingContext::create(
            Message::create('',new ParameterBag(),'', ''),
            $channel
        );

        $serializer = new MessageSerializer(
            new SymfonySerializerBridge()
        );

        /** @var OutboundMessageContext $outboundContext */
        $outboundContext = OutboundMessageContext::fromIncoming($incomingContext, $serializer);

        $this->context = $this->getMockBuilder(TestApplicationContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['applyOutboundMessageContext'])
            ->getMock();

        $this->context->applyOutboundMessageContext($outboundContext);

        $this->validator = (new ValidatorBuilder())
            ->enableAnnotationMapping()
            ->getValidator();

        configureAnnotationsLoader();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->validator, $this->context);
    }

    /**
     * @test
     *
     * @return void
     */
    public function invokeWithoutViolations(): void
    {
        $closure = \Closure::fromCallable(
            function()
            {

            }
        );

        $options = new CommandExecutionParameters('default');
        $task = new ValidateInterceptor(
            Task::new($closure, $options),
            $this->validator
        );

        static::assertEquals($options, $task->getOptions());

        $task(new TestServiceCommand(), $this->context);
    }

    /**
     * @test
     *
     * @return void
     */
    public function invokeWithViolations(): void
    {
        $closure = \Closure::fromCallable(
            function()
            {
            }
        );

        $options = new CommandExecutionParameters('default');
        $task = new ValidateInterceptor(
            Task::new($closure, $options),
            $this->validator
        );

        static::assertEquals($options, $task->getOptions());

        $task(TestServiceEvent::create([]), $this->context);
    }
}
