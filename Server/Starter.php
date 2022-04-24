<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server;

use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Kernel\Server\Event\FinishHandlerInterface;
use Nyxio\Contract\Kernel\Server\Event\RequestHandlerInterface;
use Nyxio\Contract\Kernel\Server\Event\StartHandlerInterface;
use Nyxio\Contract\Kernel\Server\Event\TaskHandlerInterface;
use Nyxio\Contract\Kernel\Server\Event\WorkerStartHandlerInterface;
use Nyxio\Contract\Kernel\Server\Job\Pool\ConnectionPoolInterface;
use Nyxio\Contract\Kernel\Server\Job\Pool\ConnectionPoolProviderInterface;
use Swoole\Constant;
use Swoole\Http\Server;

class Starter
{
    private const SERVER_LISTENERS = [
        'start' => StartHandlerInterface::class,
        'request' => RequestHandlerInterface::class,
        'finish' => FinishHandlerInterface::class,
        'task' => TaskHandlerInterface::class,
        'workerStart' => WorkerStartHandlerInterface::class,
    ];

    public function __construct(
        private readonly Server $server,
        private readonly ConnectionPoolProviderInterface $connectionPoolProvider,
        private readonly ConnectionPoolInterface $connectionPool,
        private readonly ContainerInterface $container,
    ) {
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function start(): bool
    {
        $this->serverEventBindings();
        $this->connectionPoolInit();

        /** @psalm-suppress NullableReturnStatement */
        return $this->server->start();
    }

    private function connectionPoolInit(): void
    {
        /** @psalm-suppress UndefinedClass Constant */
        $tasksCount = $this->server->setting[Constant::OPTION_TASK_WORKER_NUM];

        /** @psalm-suppress UndefinedClass Constant */
        $workersCount = $this->server->setting[Constant::OPTION_WORKER_NUM];

        foreach ($this->connectionPoolProvider->getAllRegisterClosures() as $key => $closure) {
            try {
                for ($workerId = $tasksCount - 1; $workerId >= 0; $workerId--) {
                    $this->connectionPool->add(
                        $workersCount + $workerId,
                        $key,
                        $closure(),
                    );
                }
            } catch (\Throwable $exception) {
                echo
                    \sprintf(
                        'Connection Pool Provider (%s) create instance error: %s',
                        $key,
                        $exception->getMessage()
                    )
                    . \PHP_EOL;
            }
        }
    }

    private function serverEventBindings(): void
    {
        foreach (self::SERVER_LISTENERS as $event => $class) {
            if (!$this->container->hasSingleton($class)) {
                continue;
            }

            $this->server->on(
                $event,
                [$this->container->get($class), 'handle']
            );
        }
    }
}
