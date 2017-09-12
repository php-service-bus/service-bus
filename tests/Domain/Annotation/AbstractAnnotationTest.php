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

namespace Desperado\Framework\Tests\Domain\Annotation;

use Desperado\Framework\Domain\Annotation\AbstractAnnotation;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class AbstractAnnotationTest extends TestCase
{
    /**
     * Annotation instance
     *
     * @var AbstractAnnotation
     */
    private $annotation;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->annotation = new class([]) extends AbstractAnnotation
        {
            public $existsProperty;
        };
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->annotation);
    }

    /**
     * @test
     *
     * @return void
     */
    public function setExistsProperty(): void
    {
        $this->annotation->existsProperty = 'value';

        static::assertEquals('value', $this->annotation->existsProperty);
    }

    /**
     * @test
     * @expectedException \BadMethodCallException
     *
     * @return void
     */
    public function setNonexistentProperty(): void
    {
        $this->annotation->missedProperty = 'qwerty';
    }

    /**
     * @test
     * @expectedException \BadMethodCallException
     *
     * @return void
     */
    public function getNonexistentProperty(): void
    {
        $this->annotation->missedProperty;
    }
}
