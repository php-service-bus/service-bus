<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Sagas\Events;

use Desperado\Domain\DateTime;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\ServiceBus\AbstractSaga;
use Desperado\ServiceBus\Saga\SagaState;

/**
 * The status of the saga was changed
 */
final class SagaStatusWasChangedEvent extends AbstractEvent
{
    /**
     * Saga identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Saga identifier class namespace
     *
     * @var string
     */
    protected $identifierNamespace;

    /**
     * Saga class namespace
     *
     * @var string
     */
    protected $sagaNamespace;

    /**
     * Previous saga status
     *
     * @var int
     */
    protected $previousStatusId;

    /**
     * New saga status
     *
     * @var int
     */
    protected $newStatusId;

    /**
     * Operation date
     *
     * @var string
     */
    protected $datetime;

    /**
     * Some operation description
     *
     * @var string|null
     */
    protected $description;

    /**
     * @param AbstractSaga $abstractSaga
     * @param null|string  $description
     *
     * @return self
     */
    public static function completed(AbstractSaga $abstractSaga, ?string $description = null): self
    {
        return self::create([
            'id'                  => $abstractSaga->getIdentityAsString(),
            'identifierNamespace' => $abstractSaga->getId()->getIdentityClass(),
            'sagaNamespace'       => \get_class($abstractSaga),
            'previousStatusId'    => $abstractSaga->getState()->getStatusCode(),
            'newStatusId'         => SagaState::STATUS_COMPLETED,
            'datetime'            => DateTime::nowToString(),
            'description'         => $description
        ]);
    }

    /**
     * @param AbstractSaga $abstractSaga
     *
     * @return self
     */
    public static function expired(AbstractSaga $abstractSaga): self
    {
        return self::create([
            'id'                  => $abstractSaga->getIdentityAsString(),
            'identifierNamespace' => $abstractSaga->getId()->getIdentityClass(),
            'sagaNamespace'       => \get_class($abstractSaga),
            'previousStatusId'    => $abstractSaga->getState()->getStatusCode(),
            'newStatusId'         => SagaState::STATUS_EXPIRED,
            'datetime'            => DateTime::nowToString()
        ]);
    }

    /**
     * @param AbstractSaga $abstractSaga
     * @param null|string  $description
     *
     * @return self
     */
    public static function failed(AbstractSaga $abstractSaga, ?string $description = null): self
    {
        return self::create([
                'id'                  => $abstractSaga->getIdentityAsString(),
                'identifierNamespace' => $abstractSaga->getId()->getIdentityClass(),
                'sagaNamespace'       => \get_class($abstractSaga),
                'previousStatusId'    => $abstractSaga->getState()->getStatusCode(),
                'newStatusId'         => SagaState::STATUS_FAILED,
                'datetime'            => DateTime::nowToString(),
                'description'         => $description
            ]
        );
    }

    /**
     * Get saga identifier
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get saga identifier class namespace
     *
     * @return string
     */
    public function getIdentifierNamespace(): string
    {
        return $this->identifierNamespace;
    }

    /**
     * Get saga class namespace
     *
     * @return string
     */
    public function getSagaNamespace(): string
    {
        return $this->sagaNamespace;
    }

    /**
     * Get old saga status
     *
     * @return int
     */
    public function getPreviousStatusId(): int
    {
        return $this->previousStatusId;
    }

    /**
     * Get new saga status
     *
     * @return int
     */
    public function getNewStatusId(): int
    {
        return $this->newStatusId;
    }

    /**
     * Get operation datetime
     *
     * @return string
     */
    public function getDatetime(): string
    {
        return $this->datetime;
    }

    /**
     * Get operation description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
}
