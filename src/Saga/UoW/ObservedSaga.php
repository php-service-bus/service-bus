<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\UoW;

use Desperado\ServiceBus\AbstractSaga;

/**
 *
 */
class ObservedSaga
{
    /**
     * Saga instance
     *
     * @var AbstractSaga
     */
    private $saga;

    /**
     * This is a new saga?
     *
     * @var bool
     */
    private $isNew;

    /**
     * @param AbstractSaga $saga
     *
     * @return self
     */
    public static function new(AbstractSaga $saga): self
    {
        $self = new self();

        $self->saga = $saga;
        $self->isNew = true;

        return $self;
    }

    /**
     * @param AbstractSaga $saga
     *
     * @return self
     */
    public static function saved(AbstractSaga $saga): self
    {
        $self = new self();

        $self->saga = $saga;
        $self->isNew = false;

        return $self;
    }

    /**
     * Get saga
     *
     * @return AbstractSaga
     */
    public function getSaga(): AbstractSaga
    {
        return $this->saga;
    }

    /**
     * Get a flag indicating the newly created saga
     *
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
