<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Domain\Messages;

use Desperado\ConcurrencyFramework\Domain\Messages\Exceptions\MessageSerializerException;
use Desperado\ConcurrencyFramework\Domain\ParameterBag;

/**
 * Serialized message DTO
 */
final class SerializedMessage
{
    /**
     * Message payload
     *
     * @var array
     */
    private $payload;

    /**
     * Message class namespace
     *
     * @var string
     */
    private $messageClassNamespace;

    /**
     * Metadata
     *
     * @var ParameterBag
     */
    private $metadataBag;

    /**
     * Create message
     *
     * @param MessageInterface $message
     * @param array            $normalizedPayload
     * @param ParameterBag     $metadataBag
     *
     * @return self
     */
    public static function create(
        MessageInterface $message,
        array $normalizedPayload,
        ParameterBag $metadataBag = null
    ): self
    {
        return new self(
            $normalizedPayload,
            \get_class($message),
            null !== $metadataBag ? $metadataBag : new ParameterBag()
        );
    }

    /**
     * Restore message
     *
     * @param array $normalizedPayload
     *
     * @return self
     *
     * @throws MessageSerializerException
     */
    public static function restore(array $normalizedPayload): self
    {
        if(
            true === isset($normalizedPayload['payload']) &&
            true === isset($normalizedPayload['messageClassNamespace'])
        )
        {
            if(true === \class_exists($normalizedPayload['messageClassNamespace']))
            {
                return new self(
                    $normalizedPayload['payload'],
                    $normalizedPayload['messageClassNamespace'],
                    !empty($normalizedPayload['metadata'])
                        ? new ParameterBag($normalizedPayload['metadata'])
                        : new ParameterBag()
                );
            }

            throw new MessageSerializerException(
                \sprintf(
                    'Class "%s" not found',
                    (string) $normalizedPayload['messageClassNamespace']
                )
            );
        }

        throw new MessageSerializerException(
            \sprintf(
                'In the normalized representation, the following fields should be specified: '
                . 'payload (exists: %s), messageClassNamespace (exists: %s)',
                isset($normalizedPayload['payload']) ? 'yes' : 'no',
                isset($normalizedPayload['messageClassNamespace']) ? 'yes' : 'no'

            )
        );
    }

    /**
     * Get as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'payload'               => $this->payload,
            'messageClassNamespace' => $this->messageClassNamespace,
            'metadata'              => $this->metadataBag->all()
        ];
    }

    /**
     * Get normalized payload
     *
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Get metadata parameter bag
     *
     * @return ParameterBag
     */
    public function getMetadata(): ParameterBag
    {
        return $this->metadataBag;
    }

    /**
     * Get message class namespace
     *
     * @return string
     */
    public function getMessageClassNamespace(): string
    {
        return $this->messageClassNamespace;
    }

    /**
     * @param array        $payload
     * @param string       $messageClassNamespace
     * @param ParameterBag $metadataBag
     */
    private function __construct(array $payload, string $messageClassNamespace, ParameterBag $metadataBag)
    {
        $this->payload = $payload;
        $this->messageClassNamespace = $messageClassNamespace;
        $this->metadataBag = $metadataBag;
    }
}
