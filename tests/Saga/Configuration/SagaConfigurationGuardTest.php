<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Configuration;

use Desperado\ServiceBus\Saga\Configuration\Guard\SagaConfigurationGuard;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SagaConfigurationGuardTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Configuration\Exceptions\EmptyExpirationDateModifierException
     * @expectedExceptionMessage The modifier of the expiration date of the saga is not specified. Please specify the
     *                           value "expireDateModifier" in Desperado\ServiceBus\Annotations\Sagas\Saga annotation
     *
     * @return void
     */
    public function emptyExpireDate(): void
    {
        SagaConfigurationGuard::assertExpireDateIsValid('');
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Configuration\Exceptions\IncorrectExpirationDateModifierException
     * @expectedExceptionMessage The date of the saga's fading should be correct and greater than the current date
     *
     * @return void
     */
    public function invalidExpireDateRange(): void
    {
        SagaConfigurationGuard::assertExpireDateIsValid('-1 day');
    }

    /**
     * @test
     *
     * @return void
     */
    public function validExpireDate(): void
    {
        SagaConfigurationGuard::assertExpireDateIsValid('+1 day');
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Configuration\Exceptions\EmptyIdentifierNamespaceException
     * @expectedExceptionMessage The namespace of the saga identifier class is not specified. Please specify the value
     *                           "identifierNamespace" in Desperado\ServiceBus\Annotations\Sagas\Saga annotation
     *
     * @return void
     */
    public function emptyClassIdentifierNamespace(): void
    {
        SagaConfigurationGuard::assertIdentifierClassNamespaceIsValid('');
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Configuration\Exceptions\IdentifierClassNotFoundException
     * @expectedExceptionMessage The incorrect namespace of the saga identifier class is specified
     *                           ("SomeNamespace\SomeClass")
     *
     * @return void
     */
    public function nonExistsClassIdentifierNamespace(): void
    {
        SagaConfigurationGuard::assertIdentifierClassNamespaceIsValid('SomeNamespace\SomeClass');
    }

    /**
     * @test
     *
     * @return void
     */
    public function validClassIdentifierNamespace(): void
    {
        SagaConfigurationGuard::assertIdentifierClassNamespaceIsValid(__CLASS__);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Configuration\Exceptions\EmptyIdentifierFieldValueException
     * @expectedExceptionMessage The field that contains the saga identifier must be specified. Please specify the
     *                           value "containingIdentifierProperty" in Desperado\ServiceBus\Annotations\Sagas\Saga annotation
     *
     * @return void
     */
    public function emptyContainingIdentifierPropertySpecified(): void
    {
        SagaConfigurationGuard::assertContainingIdentifierPropertySpecified('');
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Configuration\Exceptions\InvalidEventSubscriberArgumentException
     * @expectedExceptionMessage The event handler for the saga "SomeSagaNamespace" should take the first argument to
     *                           the object that implements the "Desperado\Domain\Message\EventInterface" interface
     *
     * @return void
     */
    public function invalidFirstEventListenerArgument(): void
    {
        $class = new class ()
        {
            /**
             * @param \stdClass $class
             *
             * @return void
             */
            protected function onSomeEvent(\stdClass $class): void
            {

            }
        };

        SagaConfigurationGuard::guardFirstEventListenerArgumentIsEvent(
            'SomeSagaNamespace',
            (new \ReflectionMethod($class, 'onSomeEvent'))->getParameters()
        );
    }
}
