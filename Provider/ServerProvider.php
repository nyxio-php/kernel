<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract;
use Nyxio\Kernel;
use Swoole\Http\Server;

class ServerProvider implements Contract\Provider\ProviderInterface
{
    public function __construct(
        private readonly Contract\Container\ContainerInterface $container,
        private readonly Contract\Config\ConfigInterface $config,
    ) {
    }

    public function process(): void
    {
        $this->container->singletonFn(Server::class, function () {
            $server = new Server(
                $this->config->get('server.host', '127.0.0.1'),
                $this->config->get('server.port', 9501)
            );

            $server->set($this->config->get('server.options', []));

            return $server;
        });

        $this->jobs();
        $this->connectionPool();
        $this->eventHandlers();

        $this->container->singleton(Kernel\Server\Starter::class);
    }

    private function eventHandlers(): void
    {
        $this->container->singleton(
            Contract\Kernel\Server\Event\StartHandlerInterface::class,
            Kernel\Server\Event\StartHandler::class
        );

        $this->container->singleton(
            Contract\Kernel\Server\Event\RequestHandlerInterface::class,
            Kernel\Server\Event\RequestHandler::class
        );

        $this->container->singleton(
            Contract\Kernel\Server\Event\TaskHandlerInterface::class,
            Kernel\Server\Event\TaskHandler::class
        );

        $this->container->singleton(
            Contract\Kernel\Server\Event\FinishHandlerInterface::class,
            Kernel\Server\Event\FinishHandler::class
        );

        $this->container->singleton(
            Contract\Kernel\Server\Event\WorkerStartHandlerInterface::class,
            Kernel\Server\Event\WorkerStartHandler::class
        );
    }

    private function connectionPool(): void
    {
        $this->container->singleton(
            Contract\Kernel\Server\Job\Pool\ConnectionPoolProviderInterface::class,
            Kernel\Server\Job\Pool\ConnectionPoolProvider::class,
        );

        $this->container->singleton(
            Contract\Kernel\Server\Job\Pool\ConnectionPoolInterface::class,
            Kernel\Server\Job\Pool\ConnectionPool::class,
        );
    }

    private function jobs(): void
    {
        $this->container->singleton(
            Contract\Kernel\Server\Job\DispatcherInterface::class,
            Kernel\Server\Job\Dispatcher::class
        );

        // Await
        $this->container->singleton(
            Contract\Kernel\Server\Job\Await\AwaitTaskInterface::class,
            Kernel\Server\Job\Await\AwaitTask::class
        );

        // Async
        $this->container->singleton(
            Contract\Kernel\Server\Job\Await\TaskHandlerInterface::class,
            Kernel\Server\Job\Await\TaskHandler::class,
        );

        $this->container->singleton(
            Contract\Kernel\Server\Job\Async\Queue\QueueInterface::class,
            Kernel\Server\Job\Async\Queue\Queue::class
        );

        $this->container->singleton(
            Contract\Kernel\Server\Job\Async\Schedule\ScheduleDispatcherInterface::class,
            Kernel\Server\Job\Async\Schedule\ScheduleDispatcher::class
        );

        $this->container->singleton(
            Contract\Kernel\Server\Job\Async\TaskHandlerInterface::class,
            Kernel\Server\Job\Async\TaskHandler::class,
        );
    }
}
