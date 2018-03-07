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

/**
 * A new saga was created
 */
final class SagaCreatedEvent extends AbstractEvent
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
     * Date of creation of the saga
     *
     * @var string
     */
    protected $createdAt;

    /**
     * The expiration date of the saga
     *
     * @var string
     */
    protected $expireDate;

    /**
     * @param AbstractSaga $saga
     * @param string       $expirePeriod
     *
     * @return self
     *
     * @throws \Desperado\Domain\Message\Exceptions\OverwriteProtectedPropertyException
     */
    public static function new(AbstractSaga $saga, string $expirePeriod): self
    {
        return self::create([
            'id'                  => $saga->getId()->toString(),
            'identifierNamespace' => $saga->getId()->getIdentityClass(),
            'sagaNamespace'       => \get_class($saga),
            'createdAt'           => DateTime::nowToString(),
            'expireDate'          => DateTime::fromString($expirePeriod)->toString()
        ]);
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
     * Get the date of creation of the saga
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Get the expiration date of the saga
     *
     * @return string
     */
    public function getExpireDate(): string
    {
        return $this->expireDate;
    }
}
