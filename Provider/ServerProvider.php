<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Kernel\Server\ServerEventHandlerInterface;
use Nyxio\Contract\Provider\ProviderInterface;
use Nyxio\Kernel\Server\Http\Event\RequestEventHandler;
use Nyxio\Kernel\Server\Http\Event\StartEventHandler;

class ServerProvider implements ProviderInterface
{
    public function __construct(
        private readonly ServerEventHandlerInterface $eventHandler
    ) {
    }

    /**
     * @return void
     *
     * @codeCoverageIgnore
     */
    public function process(): void
    {
        $this->eventHandler->attach('start', StartEventHandler::class);
        $this->eventHandler->attach('request', RequestEventHandler::class);
    }
}
