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

namespace Desperado\ServiceBus\Tests\Infrastructure\Storage\SQL\ActiveRecord;

use Desperado\ServiceBus\Infrastructure\Storage\SQL\ActiveRecord\Table;

/**
 * @property string   $title
 * @property-read int $pk
 */
final class SecondTestTable extends Table
{
    /**
     * @inheritDoc
     */
    protected static function tableName(): string
    {
        return 'second_test_table';
    }

    /**
     * @inheritDoc
     */
    protected static function primaryKey(): string
    {
        return 'pk';
    }
}
