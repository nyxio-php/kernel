<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Queue;

use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Queue\OptionsInterface;
use Nyxio\Contract\Queue\QueueInterface;
use Nyxio\Kernel\Server\WorkerData;
use Swoole\Http\Server;

class Queue implements QueueInterface
{
    public function __construct(private readonly Server $server, private readonly ConfigInterface $config)
    {
    }

    public function push(
        string $job,
        array $data = [],
        ?OptionsInterface $options = null
    ): void {
        $idleWorkerId = $this->getIdleWorkerId();

        $this->server->task(new WorkerData($job, $data, $options), $idleWorkerId);
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
