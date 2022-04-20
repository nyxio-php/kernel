<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Queue;

use Nyxio\Contract\Queue\OptionsInterface;
use Nyxio\Contract\Queue\QueueInterface;
use Nyxio\Kernel\Server\WorkerData;
use Swoole\Http\Server;

class Queue implements QueueInterface
{

    public function __construct(private readonly Server $server)
    {
    }

    public function push(
        string $job,
        array $data = [],
        ?OptionsInterface $options = null
    ): void {
        $workerData = new WorkerData($job, $data, $options);
        $taskId = $this->server->task(data: $workerData);

        if ($taskId === false) {
            $this->server->after(5000, function () use ($workerData) {
                $this->server->task($workerData);
            });
        }
    }
}
