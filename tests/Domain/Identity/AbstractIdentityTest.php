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

namespace Desperado\Framework\Tests\Domain\Identity;

use Desperado\Framework\Tests\TestFixtures\Identity\TestIdentity;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class AbstractIdentityTest extends TestCase
{
    /**
     * Test identity
     *
     * @var TestIdentity
     */
    private $identity;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->identity = new TestIdentity('testIdentityValue');
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->identity);
    }

    /**
     * @test
     *
     * @return void
     */
    public function identityToString(): void
    {
        static::assertEquals('testIdentityValue', $this->identity->toString());
        static::assertEquals('testIdentityValue', (string) $this->identity);
    }

    /**
     * @test
     *
     * @return void
     */
    public function identityToCompositeKey(): void
    {
        $expectedCompositeIndex = \sprintf('%s:testIdentityValue', TestIdentity::class);

        static::assertEquals($expectedCompositeIndex, $this->identity->toCompositeIndex());
        static::assertEquals(\sha1($expectedCompositeIndex), $this->identity->toCompositeIndexHash());
    }
}
