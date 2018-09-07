<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Index;

use Desperado\ServiceBus\Index\Exceptions\IndexNameCantBeEmpty;
use Desperado\ServiceBus\Index\Exceptions\ValueKeyCantBeEmpty;

/**
 * The key for the value stored in the index
 */
final class IndexKey
{
    /**
     * @var string
     */
    private $indexName;

    /**
     * @var string
     */
    private $valueKey;

    /**
     * @param string $indexName
     * @param string $valueKey
     *
     * @return self
     *
     * @throws \Desperado\ServiceBus\Index\Exceptions\IndexNameCantBeEmpty
     * @throws \Desperado\ServiceBus\Index\Exceptions\ValueKeyCantBeEmpty
     */
    public static function create(string $indexName, string $valueKey): self
    {
        self::assertIndexNameIsNotEmpty($indexName);
        self::assertValueKeyIsNotEmpty($valueKey);

        $self = new self();

        $self->indexName = $indexName;
        $self->valueKey  = $valueKey;

        return $self;
    }

    /**
     * @return string
     */
    public function indexName(): string
    {
        return $this->indexName;
    }

    /**
     * @return string
     */
    public function valueKey(): string
    {
        return $this->valueKey;
    }

    /**
     * @param string $indexName
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Index\Exceptions\IndexNameCantBeEmpty
     */
    private static function assertIndexNameIsNotEmpty(string $indexName): void
    {
        if('' === $indexName)
        {
            throw new IndexNameCantBeEmpty('Index name can\'t be empty');
        }
    }

    /**
     * @param string $valueKey
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Index\Exceptions\ValueKeyCantBeEmpty
     */
    private static function assertValueKeyIsNotEmpty(string $valueKey): void
    {
        if('' === $valueKey)
        {
            throw new ValueKeyCantBeEmpty('Value key can\'t be empty');
        }
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
