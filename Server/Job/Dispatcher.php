<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job;

use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Kernel\Server\Job\DispatcherInterface;
use Nyxio\Contract\Kernel\Server\Job\OptionsInterface;
use Swoole\Http\Server;

/**
 * @codeCoverageIgnore
 */
class Dispatcher implements DispatcherInterface
{
    public function __construct(private readonly Server $server, private readonly ConfigInterface $config)
    {
    }

    public function dispatch(TaskData $taskData): void
    {
        $workerId = $this->getIdleWorkerId();

        if ($workerId === -1) {
            $this->server->defer(function () use ($taskData) {
                $this->dispatch($taskData);
            });

            return;
        }

        /** @psalm-suppress InvalidArgument */
        $this->server->task(
            $taskData,
            $workerId,
            $taskData->options instanceof OptionsInterface ? $taskData->options->getFinishCallback() : null,
        );
    }

    public function getIdleWorkerId(): int
    {
        $workers = $this->config->get('server.options.task_worker_num');

        $idleWorkersIds = [];
        for ($workerId = $workers - 1; $workerId >= 0; $workerId--) {
            if ($this->server->getWorkerStatus($workerId) === \SWOOLE_WORKER_IDLE) {
                $idleWorkersIds[] = $workerId;
            }
        }

        return empty($idleWorkersIds) ? -1 : $idleWorkersIds[\array_rand($idleWorkersIds)];
    }
}
