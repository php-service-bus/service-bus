<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\CQRS\Behavior;

use Desperado\Framework\Domain\Behavior\BehaviorInterface;
use Desperado\Framework\Domain\Pipeline\PipelineInterface;
use Desperado\Framework\Domain\Task\TaskInterface;
use Desperado\Framework\Infrastructure\CQRS\Task\ValidatedTask;
use Symfony\Component\Validator;

/**
 * Validate message behavior (Symfony validator)
 */
class ValidationErrorBehavior implements BehaviorInterface
{
    /**
     * Validation handler
     *
     * @var Validator\Validator\ValidatorInterface
     */
    private $validator;

    public function __construct()
    {
        $this->validator = (new Validator\ValidatorBuilder())
            ->enableAnnotationMapping()
            ->getValidator();
    }

    /**
     * @inheritdoc
     */
    public function apply(PipelineInterface $pipeline, TaskInterface $task): TaskInterface
    {
        return new ValidatedTask($task, $this->validator);
    }
}
