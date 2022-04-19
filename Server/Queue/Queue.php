<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Queue;

use Nyxio\Contract\Queue\OptionsInterface;
use Nyxio\Contract\Queue\QueueInterface;
use Swoole\Http\Server;

class Queue implements QueueInterface
{
    public function __construct(private readonly Server $server)
    {
    }

    public function push(
        string $job,
        array $data = [],
        \Closure $finishCallback = null,
        ?OptionsInterface $options = null
    ): void {
        $this->server->task(
            data:            [
                                 'job' => $job,
                                 'data' => $data,
                                 'options' => $options,
                             ],
        );
    }
}
