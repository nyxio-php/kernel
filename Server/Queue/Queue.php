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
    /**
     * @var WorkerData[]
     */
    private array $queue = [];

    public function __construct(private readonly Server $server, private readonly ConfigInterface $config)
    {
    }

    public function push(
        string $job,
        array $data = [],
        ?OptionsInterface $options = null
    ): void {
        $workerData = new WorkerData($job, $data, $options);
        $worker = $this->checkWorkersAvailability();
        if ($worker === false) {
            $this->queue[] = $workerData;

            $this->server->after(100, [$this, 'performQueue']);

            return;
        }

        $this->server->task($workerData, $worker);
    }

    private function performQueue(): void
    {
        foreach ($this->queue as $workerData) {
            $this->push($workerData->job, $workerData->data, $workerData->options);
        }
    }

    public function checkWorkersAvailability(): int|bool
    {
        $workers = $this->config->get('server.options.worker_num') - 1;

        for ($worker = $workers; $worker >= 0; $worker--) {
            if ($this->server->getWorkerStatus($worker) === \SWOOLE_WORKER_IDLE) {
                return $worker;
            }
        }

        return false;
    }
}
