<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Application\Bootstrap;

use Desperado\ServiceBus\Application\Bootstrap\Exceptions;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ValidationFailedBootstrapTest extends TestCase
{
    /**
     * @test
     * @dataProvider failedBootDataProvider
     *
     * @param string $rootDirectoryPath
     * @param string $cacheDirectoryPath
     * @param string $environmentFilePath
     * @param string $expectedException
     * @param string $expectedExceptionMessage
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function failedBoot(
        string $rootDirectoryPath,
        string $cacheDirectoryPath,
        string $environmentFilePath,
        string $expectedException,
        string $expectedExceptionMessage
    ): void
    {
        static::expectException($expectedException);

        if('' !== $expectedExceptionMessage)
        {
            static::expectExceptionMessage($expectedExceptionMessage);
        }

        TestBootstrap::boot($rootDirectoryPath, $cacheDirectoryPath, $environmentFilePath);
    }

    /**
     * @return array
     */
    public function failedBootDataProvider(): array
    {
        return [
            [
                '/sss',
                \sys_get_temp_dir() . '/cache',
                __DIR__ . '/failed_dontenv/.empty.env',
                Exceptions\IncorrectRootDirectoryPathException::class,
                'The path to the root of the application is not correct ("/sss")'
            ],
            [
                __DIR__ . '/../../../src',
                '/root',
                __DIR__ . '/failed_dontenv/.empty.env',
                Exceptions\IncorrectCacheDirectoryFilePathException::class,
                'The path to the cache directory is not correct ("/root"). The directory must exist and be writable'
            ],
            [
                __DIR__ . '/../../../src',
                '',
                __DIR__ . '/failed_dontenv/.empty.env',
                Exceptions\IncorrectCacheDirectoryFilePathException::class,
                'The path to the cache directory is not correct (""). The directory must exist and be writable'
            ],
            [
                __DIR__ . '/../../../src',
                \sys_get_temp_dir() . '/cache',
                '/failed_dontenv/nonExists.env',
                Exceptions\IncorrectDotEnvFilePathException::class,
                'An incorrect path to the ".env" configuration file was specified ("/failed_dontenv/nonExists.env")'
            ],
            [
                __DIR__ . '/../../../src',
                \sys_get_temp_dir() . '/cache',
                __FILE__,
                Exceptions\ServiceBusConfigurationException::class,
                ''
            ],
            [
                __DIR__ . '/../../../src',
                \sys_get_temp_dir() . '/cache',
                __DIR__ . '/failed_dontenv/.empty.env',
                Exceptions\ServiceBusConfigurationException::class,
                'Application environment must be specified'
            ],
            [
                __DIR__ . '/../../../src',
                \sys_get_temp_dir() . '/cache',
                __DIR__ . '/failed_dontenv/.with_incorrect_env.env',
                Exceptions\ServiceBusConfigurationException::class,
                'This environment is incorrect. Acceptable variations: prod, dev, test'
            ],
            [
                __DIR__ . '/../../../src',
                \sys_get_temp_dir() . '/cache',
                __DIR__ . '/failed_dontenv/.without_entry_point_name.env',
                Exceptions\ServiceBusConfigurationException::class,
                'Entry point name must be specified'
            ],
            [
                __DIR__ . '/../../../src',
                \sys_get_temp_dir() . '/cache',
                __DIR__ . '/valid.env',
                Exceptions\ServiceBusConfigurationException::class,
                'Can not find service "sagaStorageKey" in the dependency container. The saga store must be configured'
            ]
        ];
    }
}
