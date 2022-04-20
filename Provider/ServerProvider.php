<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Provider\ProviderInterface;
use Nyxio\Kernel\Server\Event\FinishEventHandler;
use Nyxio\Kernel\Server\Event\RequestEventHandler;
use Nyxio\Kernel\Server\Event\StartEventHandler;
use Nyxio\Kernel\Server\Event\TaskEventHandler;
use Nyxio\Kernel\Server\Event\WorkerStartEventHandler;
use Swoole\Http\Server;

class ServerProvider implements ProviderInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly Server $server,
    ) {
    }

    /**
     * @return void
     *
     * @codeCoverageIgnore
     */
    public function process(): void
    {
        $this->container->singleton(StartEventHandler::class);
        $this->container->singleton(RequestEventHandler::class);
        $this->container->singleton(TaskEventHandler::class);
        $this->container->singleton(FinishEventHandler::class);
        $this->container->singleton(WorkerStartEventHandler::class);

        $this->server->on('start', [$this->container->get(StartEventHandler::class), 'handle']);
        $this->server->on('request', [$this->container->get(RequestEventHandler::class), 'handle']);
        $this->server->on('task', [$this->container->get(TaskEventHandler::class), 'handle']);
        $this->server->on('finish', [$this->container->get(FinishEventHandler::class), 'handle']);
        $this->server->on('workerStart', [$this->container->get(WorkerStartEventHandler::class), 'handle']);
    }
}
