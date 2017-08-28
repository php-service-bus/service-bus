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

namespace Desperado\ConcurrencyFramework\Domain;

/**
 * Immutable Datetime
 */
final class DateTime
{
    public const FORMAT_STRING = 'Y-m-d\TH:i:s.uP';

    /**
     * Datetime
     *
     * @var \DateTimeImmutable
     */
    private $dateTime;

    /**
     * Get current datetime as string
     *
     * @param string|null $format
     *
     * @return string
     */
    public static function nowToString(string $format = null): string
    {
        return self::now()->toString($format);
    }

    /**
     * Create current datetime instance
     *
     * @param string|null $timezone UTC by default
     *
     * @return DateTime
     */
    public static function now(string $timezone = null): self
    {
        return new self(
            \DateTimeImmutable::createFromFormat(
                'U.u',
                \sprintf('%.6F', \microtime(true)),
                new \DateTimeZone($timezone ?? 'UTC')
            )
        );
    }

    /**
     * Create datetime from string
     *
     * @param string      $dateTimeString
     * @param string|null $timezone UTC by default
     *
     * @return DateTime
     */
    public static function fromString(string $dateTimeString, string $timezone = null): self
    {
        return new self(
            new \DateTimeImmutable(
                $dateTimeString,
                new \DateTimeZone($timezone ?? 'UTC')
            )
        );
    }

    /**
     * Create datetime from string in specified format
     *
     * @param string      $format
     * @param string      $dateTimeString
     * @param string|null $timezone
     *
     * @return DateTime
     */
    public static function fromFormat(string $format, string $dateTimeString, string $timezone = null): self
    {
        return new self(
            \DateTimeImmutable::createFromFormat(
                $format,
                $dateTimeString,
                new \DateTimeZone($timezone ?? 'UTC')
            )
        );
    }

    /**
     * Get datetime as string
     *
     * @param string $format
     *
     * @return string
     */
    public function toString(string $format = null): string
    {
        return $this->dateTime->format($format ?? self::FORMAT_STRING);
    }

    /**
     * Get object as string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @param \DateTimeImmutable $dateTime
     */
    private function __construct(\DateTimeImmutable $dateTime)
    {
        $this->dateTime = $dateTime;
    }
}
