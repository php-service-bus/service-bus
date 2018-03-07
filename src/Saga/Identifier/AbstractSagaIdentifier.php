<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Identifier;

use Desperado\Domain\Identity\AbstractIdentity;

/**
 * Base sagas identifier class
 */
abstract class AbstractSagaIdentifier extends AbstractIdentity
{
    /**
     * Saga namespace
     *
     * @var string
     */
    private $sagaNamespace;

    /**
     * @param string $identifier
     * @param string $sagaNamespace
     *
     * @throws \Desperado\Domain\Identity\Exceptions\EmptyIdentifierException
     */
    final public function __construct(string $identifier, string $sagaNamespace)
    {
        parent::__construct($identifier);

        $this->sagaNamespace = $sagaNamespace;
    }

    /**
     * Get saga namespace
     *
     * @return string
     */
    final public function getSagaNamespace(): string
    {
        return $this->sagaNamespace;
    }
}
