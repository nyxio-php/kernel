<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Queue;

use Nyxio\Contract\Queue\OptionsInterface;
use Nyxio\Contract\Queue\QueueInterface;
use Nyxio\Kernel\Server\Http\WorkerData;
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
        $this->server->task(data: new WorkerData($job, $data, $options));
    }
}
