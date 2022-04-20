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
        $workerData = new WorkerData($job, $data, $options);
        if (!$this->checkWorkersAvailability()) {
            $this->server->after(5000, function () use ($workerData) {
                $this->server->task($workerData);
            });
            return;
        }

        $this->server->task($workerData);
    }

    public function checkWorkersAvailability(): bool
    {
        $workers = $this->config->get('server.options.worker_num')
            * $this->config->get('server.options.task_worker_num');

        for ($i = $workers - 1; $i >= 0; $i--) {
            if ($this->server->getWorkerStatus($i) === \SWOOLE_WORKER_IDLE) {
                return true;
            }
        }

        return false;
    }
}
