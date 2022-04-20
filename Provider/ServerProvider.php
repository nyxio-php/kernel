<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Kernel\Server\ServerEventHandlerInterface;
use Nyxio\Contract\Provider\ProviderInterface;
use Nyxio\Kernel\Server\Event\FinishEventHandler;
use Nyxio\Kernel\Server\Event\RequestEventHandler;
use Nyxio\Kernel\Server\Event\StartEventHandler;
use Nyxio\Kernel\Server\Event\TaskEventHandler;
use Nyxio\Kernel\Server\Event\WorkerStartEventHandler;

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
        $this->eventHandler->attach('task', TaskEventHandler::class);
        $this->eventHandler->attach('finish', FinishEventHandler::class);
        $this->eventHandler->attach('workerStart', WorkerStartEventHandler::class);
    }
}
