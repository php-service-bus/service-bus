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

namespace Desperado\ServiceBus\Infrastructure\HttpClient;

use Amp\ByteStream\InputStream;
use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\HttpClient\Data\InputFilePath;

/**
 * Form data
 */
interface FormBody
{
    /**
     * Create from form parameters
     *
     * @param array<string, string|array<string, string>> $fields
     *
     * @return static
     */
    public static function fromParameters(array $fields);

    /**
     * Add a file field to the form entity body
     *
     * @param string        $fieldName
     * @param InputFilePath $file
     *
     * @return void
     */
    public function addFile(string $fieldName, InputFilePath $file): void;

    /**
     * Add a data field to the form entity body
     *
     * @param string               $fieldName
     * @param string|integer|float $value
     *
     * @return void
     */
    public function addField(string $fieldName, $value): void;

    /**
     * Add multiple fields/files
     *
     * @param array<string, string|int|float|InputFilePath> $fields
     *
     * @return void
     */
    public function addMultiple(array $fields): void;

    /**
     * Create the HTTP message body to be sent
     *
     * @return InputStream
     */
    public function createBodyStream(): InputStream;

    /**
     * Retrieve a key-value array of headers to add to the outbound request
     *
     * @return array<string, string>
     */
    public function headers(): array;

    /**
     * Retrieve the HTTP message body length. If not available, return -1
     *
     * @return Promise
     */
    public function getBodyLength(): Promise;
}
