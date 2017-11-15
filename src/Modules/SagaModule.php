<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Modules;

use Desperado\CQRS\MessageBusBuilder;
use Desperado\Saga\Service\SagaService;

/**
 * Saga support module
 */
class SagaModule implements ModuleInterface
{
    /**
     * List of sagas
     *
     * [
     *     'someSagaNamespace' => 'someSagaIdentityClassNamespace',
     *     'someSagaNamespace' => 'someSagaIdentityClassNamespace',
     *     ....
     * ]
     *
     * @var array
     */
    private $sagas = [];

    /**
     * Saga service
     *
     * @var SagaService
     */
    private $sagaService;

    /**
     * @param SagaService $sagaService
     * @param array       $sagas
     */
    public function __construct(SagaService $sagaService, array $sagas)
    {
        $this->sagaService = $sagaService;
        $this->sagas = $sagas;
    }

    /**
     * @inheritdoc
     */
    public function boot(MessageBusBuilder $messageBusBuilder): void
    {
        foreach($this->sagas as $sagaNamespace => $sagaIdentityNamespace)
        {
            $this->sagaService->configure($messageBusBuilder, $sagaNamespace);
        }
    }
}
