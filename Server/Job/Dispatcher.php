<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job;

use Nyxio\Contract\Kernel\Server\Job\Async;
use Nyxio\Contract\Kernel\Server\Job\Await;
use Nyxio\Contract\Kernel\Server\Job\DispatcherInterface;
use Swoole\Constant;
use Swoole\Http\Server;

class Dispatcher implements DispatcherInterface
{
    public function __construct(private readonly Server $server)
    {
    }

    public function dispatch(TaskData $taskData): mixed
    {
        $workerId = $this->getIdleWorkerId();

        if ($workerId === -1) {
            $this->server->defer(function () use ($taskData) {
                $this->dispatch($taskData);
            });

            return null;
        }

        if ($taskData->isAsync()) {
            $finishCallback = null;

            if ($taskData->options instanceof Async\OptionsInterface) {
                $finishCallback = $taskData->options->getFinishCallback();
                $taskData->options->resetFinishCallback();
            }

            /** @psalm-suppress InvalidArgument */
            $this->server->task(
                $taskData,
                $workerId,
                $finishCallback,
            );

            return null;
        }

        /** @psalm-suppress InvalidArgument */
        return $this->server->taskwait(
            $taskData,
            $taskData->options instanceof Await\OptionsInterface ? $taskData->options->getTimeout() : 0.5,
            $workerId
        );
    }

    public function getIdleWorkerId(): int
    {
        /** @psalm-suppress UndefinedClass Constant */
        $workers = $this->server->setting[Constant::OPTION_WORKER_NUM] ?? null;

        if ($workers === null) {
            return -1;
        }

        $idleWorkersIds = [];

        for ($workerId = $workers - 1; $workerId >= 0; $workerId--) {
            if ($this->server->getWorkerStatus($workerId) === \SWOOLE_WORKER_IDLE) {
                $idleWorkersIds[] = $workerId;
            }
        }

        return empty($idleWorkersIds) ? -1 : $idleWorkersIds[\array_rand($idleWorkersIds)];
    }
}
