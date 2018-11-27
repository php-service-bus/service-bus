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

namespace Desperado\ServiceBus\Infrastructure\HttpClient\Artax;

use Amp\Artax\FormBody as AmpFormBody;
use Amp\ByteStream\InputStream;
use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\HttpClient\Data\InputFilePath;
use Desperado\ServiceBus\Infrastructure\HttpClient\FormBody;

/**
 * Artax form body implementation
 *
 * @codeCoverageIgnore
 */
final class ArtaxFormBody implements FormBody
{
    /**
     * Original body object
     *
     * @var AmpFormBody
     */
    private $original;

    /**
     * Boundary
     *
     * @var string
     */
    private $boundary;

    /**
     * Is multipart request
     *
     * @var bool
     */
    private $isMultipart;

    public function __construct()
    {
        $this->isMultipart = false;

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->boundary = \bin2hex(\random_bytes(16));
        $this->original = new AmpFormBody($this->boundary);
    }

    /**
     * @inheritdoc
     */
    public static function fromParameters(array $fields): self
    {
        $self = new self();
        $self->addMultiple($fields);

        return $self;
    }

    /**
     * @inheritdoc
     */
    public function addFile(string $fieldName, InputFilePath $file): void
    {
        $this->isMultipart = true;
        $this->original->addFile($fieldName, (string) $file);
    }

    /**
     * @inheritdoc
     */
    public function addField(string $fieldName, $value): void
    {
        $this->original->addField($fieldName, (string) $value);
    }

    /**
     * @inheritdoc
     */
    public function addMultiple(array $fields): void
    {
        /** @var string|float|int|InputFilePath $value */
        foreach($fields as $key => $value)
        {
            /** @psalm-suppress MixedArgument Incorrect processing of ternary operators */
            $value instanceof InputFilePath
                ? $this->addFile($key, $value)
                : $this->addField($key, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function createBodyStream(): InputStream
    {
        return $this->original->createBodyStream();
    }

    /**
     * @inheritdoc
     */
    public function headers(): array
    {
        return [
            'Content-Type' => $this->isMultipart
                ? \sprintf('multipart/form-data; boundary=%s', $this->boundary)
                : 'application/x-www-form-urlencoded'
        ];
    }

    /**
     * @inheritdoc
     */
    public function getBodyLength(): Promise
    {
        return $this->original->getBodyLength();
    }

    /**
     * @return AmpFormBody
     */
    public function preparedBody(): AmpFormBody
    {
        return $this->original;
    }
}
