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

use Desperado\ServiceBus\Services\Handlers\CommandExecutionParameters;
use Desperado\ServiceBus\Task\Interceptors\ValidateInterceptor;
use Desperado\ServiceBus\Task\Task;
use Desperado\ServiceBus\Tests\Services\Stabs\TestServiceCommand;
use Desperado\ServiceBus\Tests\Services\Stabs\TestServiceEvent;
use Desperado\ServiceBus\Tests\TestApplicationContext;
use Doctrine\Common\Annotations\AnnotationRegistry;
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
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = (new ValidatorBuilder())
            ->enableAnnotationMapping()
            ->getValidator();

        /** Configure doctrine annotations autoloader */
        foreach(\spl_autoload_functions() as $autoLoader)
        {
            if(isset($autoLoader[0]) && \is_object($autoLoader[0]))
            {
                /** @var \Composer\Autoload\ClassLoader $classLoader */
                $classLoader = $autoLoader[0];

                /** @noinspection PhpDeprecationInspection */
                AnnotationRegistry::registerLoader(
                    function(string $className) use ($classLoader)
                    {
                        return $classLoader->loadClass($className);
                    }
                );

                break;
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->validator);
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

        $task(new TestServiceCommand(), new TestApplicationContext());
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

        $task(TestServiceEvent::create([]), new TestApplicationContext());
    }
}
