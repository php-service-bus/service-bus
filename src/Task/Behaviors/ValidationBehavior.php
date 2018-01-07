<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Task\Behaviors;

use Desperado\ServiceBus\Task\Interceptors\ValidateInterceptor;
use Desperado\ServiceBus\Task\TaskInterface;
use Symfony\Component\Validator;

/**
 * Applies validation to messages (Symfony validator)
 */
class ValidationBehavior implements BehaviorInterface
{
    /**
     * Validation handler
     *
     * @var Validator\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * Create behavior
     *
     * @return ValidationBehavior
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @inheritdoc
     */
    public function apply(TaskInterface $task): TaskInterface
    {
        return new ValidateInterceptor($task, $this->validator);
    }

    /**
     * Close constructor
     */
    private function __construct()
    {
        $this->validator = (new Validator\ValidatorBuilder())
            ->enableAnnotationMapping()
            ->getValidator();
    }
}

